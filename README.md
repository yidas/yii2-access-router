<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="100px">
    </a>
    <h1 align="center">Yii 2 Access Router</h1>
    <br>
</p>

Simple access control router for Yii2 Framework

[![Latest Stable Version](https://poser.pugx.org/yidas/yii2-access-router/v/stable?format=flat-square)](https://packagist.org/packages/yidas/yii2-access-router)
[![Latest Unstable Version](https://poser.pugx.org/yidas/yii2-access-router/v/unstable?format=flat-square)](https://packagist.org/packages/yidas/yii2-access-router)
[![License](https://poser.pugx.org/yidas/yii2-access-router/license?format=flat-square)](https://packagist.org/packages/yidas/yii2-access-router)

FEATURES
--------

AccessRouter is an router filter. It will check the routes which not in $except with authorization scheme.

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

Set a component and an event into your application configuration:

```php
return [
    'components' => [
        'accessRouter' => [
            'class' => 'yidas\filters\accessRouter',
            'except' => ['site/login', 'site/logout'],
        ],
        // ...
    ],
    'on beforeAction' => function ($event) {
        Yii::$app->accessRouter->run();
    },
    // ...
];
```

---

USAGE
-----

### User Scheme (Default)

With `user` scheme, it would authorize `Yii::$app->user` with every routes not in `$except`, you need to login using `Yii::$app->user` in excepted route.

```php
    'components' => [
        'accessRouter' => [
            'class' => 'yidas\filters\accessRouter',
            'except' => ['site/login', 'site/logout'],
            //'scheme' => 'user',
        ],
```

### Key Scheme

With `key` scheme, you could login into AccessRouter with session by carring parameter from every routes.

```php
    'components' => [
        'accessRouter' => [
            'class' => 'yidas\filters\accessRouter',
            'except' => ['site/login', 'site/logout'],
            'scheme' => 'key',
            'schemeSetting' => [
                'paramKey' => 'token',
                'value' => 'e332a76c29654fcb7f6e6b31ced090c7',
            ],
        ],
```

For above example, you could login by accessing `//example.com/?token=e332a76c29654fcb7f6e6b31ced090c7` with every routes.

> The login session is implemented in AccessRouter itself.

