<?php

namespace yidas\filters;

use Yii;
use yii\base\Component;
 
/**
 * Access Routing Filter
 * 
 * @author  Nick Tsai <myintaer@gmail.com>
 * @version 1.0.0
 */
class AccessRouter extends Component
{
    /**
     * The route key of $except for access denied
     *
     * @var integer
     */
    public $exceptRouteKey = 0;
    
    /**
     * Exception routes
     *
     * @var array Yii routes
     */
    public $except = ['site/login'];

    /**
     * Except error action
     * 
     * Error action would be excepted through filter while turning on
     *
     * @var boolean
     */
    public $exceptErrorAction = true;
    
    /**
     * Authorization scheme
     * 
     * user : Yii official access control authorized by Yii::$app->user
     * key  : Simple authorization by carring key
     *
     * @var string user|key
     */
    public $scheme = 'user';

    /**
     * Setting for current authorization scheme 
     *
     * @var array
     * @todo Now is only for key scheme
     */
    public $schemeSetting = [];

    /**
     * Default setting for each authorization scheme 
     *
     * @var array
     */
    public $_schemeDefaultSetting = [
        // Key scheme
        'key' => [
            /**
             * Token for accessing on `key` scheme mode
             *
             * @var string
             */
            'value' => 'e332a76c29654fcb7f6e6b31ced090c7',
            /**
             * Param method for carring token on `key` scheme mode
             *
             * @var string
             */
            'method' => 'get',
            /**
             * Param key on `key` scheme mode
             *
             * @var string
             */
            'paramKey' => 'key',
            /**
             * Session Key on `key` scheme mode
             *
             * @var string
             */
            'sessionKey' => 'access-key',
        ],
    ];

    /**
     * Initialization
     *
     * @return void
     */
    public function init()
    {
        // Setting initialization
        $this->schemeSetting = array_merge($this->_schemeDefaultSetting[$this->scheme], $this->schemeSetting);
    }

    /**
     * Aliase of authorize()
     *
     * @return void
     */
    public function run()
    {
        return $this->authorize();
    }

    /**
     * Authentication
     *
     * @return void
     */
    public function authorize()
    {
        $route = Yii::$app->controller->id . '/' . Yii::$app->controller->action->id;

        // Except error action check
        if ($this->exceptErrorAction && $route==Yii::$app->errorHandler->errorAction) {
            // No need to check auth
            return true;
        }
        // Except check
        if (in_array($route, $this->except)) {
            // No need to check auth
            return true;
        }
        // isGuest check
        if (!$this->isGuest()) {
            // is login
            return true;
        }
        
        // Login bootstrap
        switch ($this->scheme) {
            // Token type could login anywhere
            case 'key':
                
            // Login entry
            $token = null;
            switch ($this->schemeSetting['method']) {
                default:
                case 'get':
                $token = Yii::$app->request->get($this->schemeSetting['paramKey']);
                    break;
            }
            // Validate token
            if ($token == $this->schemeSetting['value']) {
                
                Yii::$app->session->set($this->schemeSetting['sessionKey'], $token);
                // Auth success
                return true;
            }

                break;
            
            default:
            case 'user':
                # No bootstrap login
                break;
        }

        // Access denied route action 
        $route = isset($this->except[$this->exceptRouteKey]) 
            ? $this->except[$this->exceptRouteKey]
            : Yii::$app->errorHandler->errorAction;
        return Yii::$app->response->redirect(\yii\helpers\Url::to([$route]));
    }

    /**
     * Get is guest
     *
     * @return boolean
     */
    public function isGuest()
    {
        switch ($this->scheme) {
            // Token type
            case 'key':
                
                $token = Yii::$app->session->get($this->schemeSetting['sessionKey']);
                return ($token) ? false : true;
                break;
            
            // Default type
            default:
            case 'user':
                return Yii::$app->user->isGuest;
                break;
        }
    }
}
