<?php

namespace yidas\filters;

use Exception;
use Yii;
use yii\base\Component;
 
/**
 * Access Routing
 * 
 * @author  Nick Tsai <myintaer@gmail.com>
 * @version 1.0.0
 */
class AccessRouter extends Component
{
    /**
     * Excepted routes for identity verification check
     *
     * @var array Yii routes, '{controller}/{action}'
     */
    public $except = ['*'];

    /**
     * A callback that will be called if the access should be denied to the current user. 
     *
     * @var callable
     */
    public $denyCallback = null;

    /**
     * Except error action
     * 
     * Error action would be excepted through filter while turning on
     *
     * @var boolean
     */
    public $exceptErrorAction = true;

    /**
     * HTTP authentication framework feature
     *
     * @var array Settings referred $_httpAuthDefault
     */
    public $httpAuth = [];

    /**
     * HTTP request method login feature
     *
     * @var array Settings referred $_httpLoginDefault
     */
    public $httpLogin = [];

    /**
     * $httpAuth default setting
     *
     * @var array Settings
     */
    protected $_httpAuthDefault = [
        /**
         * @var boolean
         */
        'enable' => false,
        /**
         * @var callable DenyCallback for HTTP authentication 
         */
        'denyCallback' => null,
        /**
         * @var boolean Force to authorize by HTTP authentication 
         */
        'forced' => true,
        /**
         * @var string The header key
         */
        'key' => 'AUTHORIZATION',
    ];

    /**
     * $httpLogin default setting
     *
     * @var array Settings
     */
    protected $_httpLoginDefault = [
        /**
         * @var boolean
         */
        'enable' => false,
        /**
         * @var string Methods of get/post
         */
        'method' => 'post',
        /**
         * @var array Allowed routes for login. ['{controller}/{action}', '{d}/{c}/{a}']
         */
        'only' => ['*'],
        /**
         * @var integer Seconds of login duration
         */
        'duration' => 3600 * 24 * 30,
        /**
         * @var string Parameter's key
         */
        'key' => 'access_token',
    ];

    /**
     * Initialization
     *
     * @return void
     */
    public function init()
    {
        // User Authorization
        $this->authorize();
    }

    /**
     * Authentication
     *
     * @return void
     */
    public function authorize()
    {
        /**
         * Verification
         */
        // Check user authorization is disabled by `except` config
        if ($this->_isAsterisk($this->except)) {
            // No need to check auth
            return true;
        }

        // `except` data type check
        if (!is_array($this->except)) {
            throw new Exception("`except` setting must be an array (" . __CLASS__ . ")", 500);
        }
        // Prevent first `except` route setting wrong
        if (strpos($this->except[0], '/')===0) {
            throw new Exception("\"{$this->except[0]}\" must not have a preceding \"/\" in `except` setting (" . __CLASS__ . ")", 500);
        }
        
        // Get current route
        $route = Yii::$app->request->resolve()[0];
        
        // Except check
        if (in_array($route, $this->except)) {
            // No need to check auth
            return true;
        }
        // Except error action check
        if ($this->exceptErrorAction && $route==Yii::$app->errorHandler->errorAction) {
            // No need to check auth
            return true;
        }
        // isGuest check
        if (!Yii::$app->user->isGuest) {
            // is login
            return true;
        }

        /**
         * HTTP authentication login
         */
        $this->httpAuth = array_merge($this->_httpAuthDefault, $this->httpAuth);
        $headerValue = isset($_SERVER["HTTP_{$this->httpAuth['key']}"]) ? $_SERVER["HTTP_{$this->httpAuth['key']}"] : null;
        // HTTP Bearer header login (Forced setting concern)
        if ($this->httpAuth['enable'] && ($this->httpAuth['forced'] || $headerValue) ) {
            
            /**
             * @todo Support basic auth
             * @see  https://www.yiiframework.com/doc/api/2.0/yii-web-request#getAuthCredentials()-detail
             */
            // Bearer as default (see RFC 6750, bearer tokens to access OAuth 2.0-protected resources)
            $accessToken = (strpos(strtolower($headerValue), 'bearer ')===0) ? substr($headerValue, 7) : null;
            
            // Disable login session for HTTP authentication usage
            Yii::$app->user->enableSession = false;

            if ($accessToken && $this->loginByAccessToken($accessToken, 0)) {

                return true;

            } else {
                /**
                 * Access denied route action for HTTP authentication
                 */
                $this->httpAuth['denyCallback'] = is_callable($this->httpAuth['denyCallback']) ? $this->httpAuth['denyCallback'] : function() {

                    // JSON Response as default
                    $response = Yii::$app->response;
                    $response->statusCode = 401;
                    $response->format = \yii\web\Response::FORMAT_JSON;
                    $response->data = ['message' => 'Unauthorized'];
                    return $response->send();
                };
                
                return call_user_func_array($this->httpAuth['denyCallback'], []);
            }
        }

        /**
         * HTTP request method login
         */
        $this->httpLogin = array_merge($this->_httpLoginDefault, $this->httpLogin);
        // HTTP GET/POST login
        if ($this->httpLogin['enable'] && ($this->_isAsterisk($this->httpLogin['only']) || in_array($route, $this->httpLogin['only']))) {

            // Method switch
            switch ($this->httpLogin['method']) {
                
                case 'get':
                    $accessToken = isset($_GET[$this->httpLogin['key']]) ? $_GET[$this->httpLogin['key']] : null;
                    break;

                case 'post':
                default:
                    $accessToken = isset($_POST[$this->httpLogin['key']]) ? $_POST[$this->httpLogin['key']] : null;
                    break;
            }

            // Login
            if ($this->loginByAccessToken($accessToken, $this->httpLogin['duration'])) {

                return true;
            }
        }

        /**
         * Access denied route action 
         */
        $this->denyCallback = is_callable($this->denyCallback) ? $this->denyCallback : function() {

            // Get first excepted route to redirect
            $route = array_shift($this->except);
            $route = ($route) ?: Yii::$app->errorHandler->errorAction;
            // With root prefix
            return Yii::$app->response->redirect("/$route");
        };

        return call_user_func_array($this->denyCallback, []);
    }

    /**
     * Login by giving access token
     *
     * @param string $accessToken
     * @param integer $duration Number of seconds that the user can remain in logged-in status, defaults to 0
     * @return boolean Whether the user is logged in
     */
    public function loginByAccessToken($accessToken, $duration)
    {
        // Get yii\web\IdentityInterface instance
        $identityClass = Yii::$app->user->identityClass;

        // Verify by access token
        $user = $identityClass::findIdentityByAccessToken($accessToken);

        // Login
        if ($user) {
            
            return Yii::$app->user->login($user, $duration);
        }

        return false;
    }

    /**
     * Check the setting value is `*` present
     *
     * @param string|array $settingValue
     * @return boolean
     */
    protected function _isAsterisk($settingValue)
    {
        return ($settingValue == '*' || !isset($settingValue[0]) || $settingValue[0] == '*');
    }
}
