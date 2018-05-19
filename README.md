<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="100px">
    </a>
    <h1 align="center">Yii 2 Access Router</h1>
    <br>
</p>

Yii 2 user authentication & authorization router

[![Latest Stable Version](https://poser.pugx.org/yidas/yii2-access-router/v/stable?format=flat-square)](https://packagist.org/packages/yidas/yii2-access-router)
[![Latest Unstable Version](https://poser.pugx.org/yidas/yii2-access-router/v/unstable?format=flat-square)](https://packagist.org/packages/yidas/yii2-access-router)
[![License](https://poser.pugx.org/yidas/yii2-access-router/license?format=flat-square)](https://packagist.org/packages/yidas/yii2-access-router)

FEATURES
--------

- *Yii 2 **User Authentication/Authorization for route level** Integration*

- ***RESTful API Authentication** by Access Token support* 

- ***HTTP Request Login** by Access Token support*

Access Router is a simple user access filtered on route level which supports authentication and authorization. Different from Yii2 Access Control Filter (ACF), this User Authorization can specify routes but not only in controller-actions level.

---

OUTLINE
-------

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
    - [Options](#options)
- [Usage](#usage)
    - [Except](#except)
    - [HTTP Authentication](#http-authentication)
        - [Options](#options-1)
    - [Request Method Login](#request-method-login)
        - [Options](#options-2)
        - [POST Method without CSRF](#post-method-without-csrf)
- [Additions](#additions)
    - [ACF for Global](#acf-for-global)
- [References](#references)

---

REQUIREMENTS
------------

This library requires the following:

- PHP 5.4.0+
- Yii 2.0.0+

---

INSTALLATION
------------

Install via Composer in your Yii2 project:

```
composer require yidas/yii2-access-router
```

---

CONFIGURATION
-------------

Setup a Access Router component and then add it into bootstrap for your application configuration:

```php
return [
    'bootstrap' => ['log', 'access'],
    'components' => [
        'access' => [
            'class' => 'yidas\filters\accessRouter',
            'except' => ['site/login', 'site/register'],
            'denyCallback' => function() {
                return Yii::$app->response->redirect(['/site/login']);
            },
        ],
        // ...
    ],
    // ...
];
```

1. Create a component called `access` which uses `yidas\filters\accessRouter` as class with configuration.

2. Add this `access` component into `bootstrap` list.


### Options

|Key              |Type    |Default        |Description|
|:-               |:-      |:-             |:-|
|**except**       |array   |\['\*']        |Excepted routes for identity verification check. \['{controller}/{action}', '{d}/{c}/{a}'\]|
|**denyCallback** |callable|null           |DenyCallback for HTTP authentication|
|[httpAuth](#http-authentication)          |array   |               |HTTP authentication framework feature|
|[httpLogin](#request-method-login)        |array   |               |HTTP request method login feature|
|exceptErrorAction|boolean |true           |Error action would be excepted through filter while turning on|

---

USAGE
-----

### Except

Access Router implements Access Control Filter (ACF) for routes that the user is must in login status to pass through the filter from any routes except specified ones.

You can setup excepted routes that skip the user authorization. The `except` setting with `[*]` value means that the user authorization is disabled:

```php
'access' => [
    'class' => 'yidas\filters\AccessRouter',
    'except' => ['site/login'], //`site/login` is the login page which can not bypass user authorization
],
```

### HTTP Authentication

Access Router supports automatically authenticating client's request by HTTP Authentication with bearer schemes ([RFC 6750](https://tools.ietf.org/html/rfc6750)), you can enable it by setting up `httpAuth` configuration:

```php
'access' => [
    'class' => 'yidas\filters\AccessRouter',
    'except' => ['site/login', 'site/register'],
    'httpAuth' => [
        'enable' => true,
        'denyCallback' => function() {
            $response = Yii::$app->response;
            $response->statusCode = 401;
            $response->format = \yii\web\Response::FORMAT_JSON;
            $response->data = ['message' => 'Access Denied'];
            return $response->send();
        },
    ],
],
```

> HTTP Authentication login will disable session for one time access uasge, which equals to `\Yii::$app->user->enableSession = false;`

#### Options

|Key             |Type    |Default        |Description|
|:-              |:-      |:-             |:-|
|**enable**      |boolean |false          |Enable HTTP authentication|
|**denyCallback**|callable|null           |DenyCallback for HTTP authentication|
|forced          |boolean |true           |Force to authorize by HTTP authentication|
|key             |string  |'AUTHORIZATION'|The header key|


### Request Method Login

Access Router also supports automatically login client's request by HTTP GET/POST parameter by giving access token, you can enable it by setting up `httpLogin` configuration:

```php
'access' => [
    'class' => 'yidas\filters\AccessRouter',
    'except' => ['site/login', 'site/register'],
    'httpLogin' => [
        'enable' => true,
        'method' => 'post'
        'only' => ['site/login'],
        // 'key' => 'access_token',
    ],
],
```

For above configuration, you could login by accessing route `site/login` with correct `access_token` body value (*Content-Type: `application/x-www-form-urlencoded`*).

Request Method Login is same as form login that the session is enable, and the duration time could be customized.

For `GET` method, If you setup `'method' => 'get'` with `'only' => ['*']`, then you can login by any routes with correct `access_token` parameter. For example: `//example.com/?access_token={valid-user-access-token}`

> For security reasons, it's not recommended to use `GET` method that passes access token in parameter.

#### Options

|Key             |Type    |Default        |Description|
|:-              |:-      |:-             |:-|
|**enable**      |boolean |false          |Enable HTTP request method login|
|**method**      |string  |'post'         |Parameter's Methods of get/post|
|**only**        |array   |\['\*']        |Allowed routes for login. \['{controller}/{action}', '{d}/{c}/{a}'\]|
|duration        |integer |3600 * 24 * 30 |Seconds of login duration|
|key             |string  |'access_token' |Parameter's key|
|forced          |boolean |true           |Force to authorize by HTTP authentication|

#### POST Method without CSRF 

If you uses `post` method and want to disable global CSRF validatiob, you can set `enableCsrfValidation` to `false` for `request` configuration:

```php
'components' => [
    'request' => [
        'csrfParam' => '_csrf-backend',
        'enableCsrfValidation' => false,
    ],
```

> If you just want to disable CSRF for some controllers/actions, dynamically setting `enableCsrfValidation` for controller.

---

ADDITIONS
---------

### ACF for Global

If you want to use original Yii 2 Access Control Filter (ACF) for global route instead of Access Router's User Authorization, just comment out the `except` of Access Router and add ACF rules into 'as beforeRequest' in config:

```php
'bootstrap' => ['log', 'access'],
'components' => [
    'access' => [
        'class' => 'yidas\filters\AccessRouter',
        'except' => ['*'], // Equal to comment out
    ],
    // ...
],
'as beforeRequest' => [
    'class' => 'yii\filters\AccessControl',
    'rules' => [
        [
            'allow' => true,
            'actions' => ['login'],
        ],
        [
            'allow' => true,
            'roles' => ['@'],
        ],
    ],
    'denyCallback' => function () {
        return Yii::$app->response->redirect(['site/login']);
    },
],
```

**Warning:** ACF could only defines `actions` but not routes, which the actions could be applied by every controllers.

For above setting example, `login` excepted action could be matched by any controller such as `site/login`, `controller/login`.

---

REFERENCE
---------

[Yii 2 - Application Structure > Application Events](https://www.yiiframework.com/doc/guide/2.0/en/structure-applications#application-events)

[RFC7617 - The 'Basic' HTTP Authentication Scheme](https://tools.ietf.org/html/rfc6750)

