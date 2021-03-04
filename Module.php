<?php
/**
 * sjaakp/yii2-linkchecker
 * ----------
 * Link checker module for Yii2 framework
 * Version 1.0.0
 * Copyright (c) 2021
 * Sjaak Priester, Amsterdam
 * MIT License
 * https://github.com/sjaakp/yii2-linkchecker
 * https://sjaakpriester.nl
 */

namespace sjaakp\linkchecker;

use Yii;
use yii\base\BootstrapInterface;
use yii\base\InvalidConfigException;
use yii\base\Module as YiiModule;
use yii\console\Application as ConsoleApplication;
use yii\helpers\ArrayHelper;
use yii\web\Application as WebApplication;
use yii\web\GroupUrlRule;
use sjaakp\linkchecker\models\Link;

/**
 * linkchecker module definition class
 */
class Module extends YiiModule implements BootstrapInterface
{
    /**
     * @var array where to look for URLs
     * Array of arrays with the following format:
     * [
     *      'model' => <fully qualified classname, or table name>
     *      'where' => <where-condition limiting scanned models; default: none>
     *      'urlAttributes' => <attribute(s) with an URL-value>
     *      'htmlAttributes' => <attributes(s) containing HTML, possibly with embedded URLs>
     *      'mode' => <which URLs to consider: 'abs' (absolute, default), 'rel' (relative), or 'both'>
     * ]
     *
     * Both 'xxxAttributes' fields can be:
     * - null (default): no such attributes
     * - string: one attribute name
     * - array of strings: multiple attributes
     *
     * where condition: parameter of Yii's yii\db\QueryInterface::where()
     * @link https://www.yiiframework.com/doc/api/2.0/yii-db-queryinterface#where()-detail
     */
    public $source = [];

    /**
     * @var array URLs not taken into consideration.
     * These may be literal strings, or regular expressions (PCRE) without delimiters.
     * @link https://www.php.net/manual/en/pcre.pattern.php
     */
    public $greenlist = [];

    /**
     * @var int maximum number of requests handled simultaneously.
     */
    public $maxRequests = 40;

    /**
     * @var int timeout in milliseconds
     */
    public $timeout = 3000;

    /**
     * @var array extra options to be used in curl requests
     * There are lots of them: @link https://www.php.net/manual/en/function.curl-setopt.php
     * Use with care!
     */
    public $curlOptions = [];

    /**
     * @var string name of the linkchecker database table
     */
    public $tableName = '{{%linkchecker}}';

    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'sjaakp\linkchecker\controllers';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        if (version_compare(curl_version()['version'], '7.16.2') < 0)  {
            throw new InvalidConfigException('Linkchecker: curl version is older than 7.16.2.');
        }

        Link::$_tableName = $this->tableName;

        if (! isset( Yii::$app->i18n->translations['linkchecker']))   {
            Yii::$app->i18n->translations['linkchecker'] = [
                'class' => 'yii\i18n\PhpMessageSource',
                'sourceLanguage' => 'en-US',
                'basePath' => '@sjaakp/linkchecker/messages',
            ];
        }
    }

    public function bootstrap($app)
    {
        if ($app instanceof WebApplication) {
            $rules = new GroupUrlRule([
                'prefix' => $this->id,
                'rules' => [
                    '' => 'default/index',
                    '<id:\d+>' => 'default/view',
                    '<a:[\w\-]+>/<id:\d+>' => 'default/<a>',
                    '<a:[\w\-]+>' => 'default/<a>',
                ]
            ]);
            $app->getUrlManager()->addRules([$rules], false);

        } else {
            /* @var $app ConsoleApplication */

            $app->controllerMap = ArrayHelper::merge($app->controllerMap, [
                'migrate' => [
                    'class' => '\yii\console\controllers\MigrateController',
                    'migrationNamespaces' => [
                        'sjaakp\linkchecker\migrations'
                    ]
                ],
            ]);
        }
    }
}
