yii2-linkchecker
================

## Scan links for errors with Yii2 PHP Framework

[![Latest Stable Version](https://poser.pugx.org/sjaakp/yii2-linkchecker/v/stable)](https://packagist.org/packages/sjaakp/yii2-linkchecker)
[![Total Downloads](https://poser.pugx.org/sjaakp/yii2-linkchecker/downloads)](https://packagist.org/packages/sjaakp/yii2-linkchecker)
[![License](https://poser.pugx.org/sjaakp/yii2-linkchecker/license)](https://packagist.org/packages/sjaakp/yii2-linkchecker)

**Linkchecker** is a module for the [Yii 2.0](https://www.yiiframework.com/ "Yii") PHP Framework. 
It can check all the links and image sources stored in the web site's database. 

## Installation ##

Install **yii2-linkchecker** in the usual way with [Composer](https://getcomposer.org/).
Add the following to the require section of your `composer.json` file:

`"sjaakp/yii2-linkchecker": "*"`

or run:

`composer require sjaakp/yii2-linkchecker`

You can manually install **yii2-linkchecker** by [downloading the source in ZIP-format](https://github.com/sjaakp/yii2-linkchecker/archive/master.zip).

#### Module ####

**Linkchecker** is a [module](https://www.yiiframework.com/doc/guide/2.0/en/structure-modules#using-modules "Yii2")
in the Yii2 framework. It has to be configured
in the main configuration file, usually called `web.php` or `main.php` in the `config`
directory. Add the following to the configuration array:

    <?php
    // ...
    'modules' => [
        'linkchecker' => [
            'class' => 'sjaakp\linkchecker\Module',
            // several options
        ],
    ],
    // ...


The module has to be *bootstrapped*. Do this by adding the following to the
application configuration array:

    <php
    // ...
    'bootstrap' => [
        'linkchecker',
    ]
    // ...

There probably already is a `bootstrap` property in your configuration file; just
add `'linkchecker'` to it.

**Important**: the module should also be set up in the same way in the console configuration (usually
called `console.php`).

#### Console command ####

To complete the installation, a [console command](https://www.yiiframework.com/doc/guide/2.0/en/tutorial-console#usage "Yii2")
has to be run. It will create a database table for the checked URLs:

    yii migrate

The migration applied is called `sjaakp\linkchecker\migrations\m000000_000000_init`.

## Usage ##

After installation, **Linkchecker** reports its findings on `www.example.com/linkchecker`.
Initially, the list shown there will be empty. The page sports a 'Scan' button.
After clicking it, **Linkchecker** will collect all the URL links and image URIs
(`href`, `src`, and `srcset` values),
store them in its database table, and try to access them. This process may take
some time, heavily dependent on the quality of the URLs. Checking over 400 URL's
typically costed less than 20 seconds on my test system with a high speed internet 
connection.

For this to work, **Linkchecker** has to be instructed where to look for URLs.
This involves the setting of the `source`-option of the module.

Suppose we have a web site publishing very interesting articles. It is built
with two models:

    /**
    * @property string $mainHtml    // contains HTML with href's and src's
    * @property string $asideHtml   // likewise
    * ... other attributes, like $title, $publicationDate ...
    */
    class Article extends ActiveRecord
    {
        // ... Article methods and stuff ...
    }

    /**
    * @property string $homepageUrl   // contains pure URL
    * ... other attributes, like $name, $birthday ...
    */
    class Author extends ActiveRecord
    {
        // ... Author methods and stuff ...
    }

We can instruct **Linkchecker** to check the URLs in both classes by initializing
the module like so:

    <?php
    // ...
    'modules' => [
        'linkchecker' => [
            'class' => 'sjaakp\linkchecker\Module',
            'source' => [
                [
                    'model' => 'app\models\Article',
                    'htmlAttributes' => [ 'mainHtml', 'asideHtml']
                ],
                [
                    'model' => 'app\models\Author',
                    'urlAttributes' => 'homepageUrl'
                ],
            ]
            // ... other options ...
        ],
    ],
    // ...

- **source** is an `array` of `array`s, describing the places where **Linkchecker**
should look for URLs. They have the following fields:

    - **model**: the fully classified class name of the `ActiveRecord`. It can also
        be a table name.
    - **htmlAttributes**: one (`string`) ore more (`array` of `string`s) names of
        attributes containing HTML. **Linkchecker** will distill URLs of all the 
        `href`s, `src`s, and `srcset`s in the HTML. Default: `[ ]` (empty array).
    - **urlAttributes**: one (`string`) ore more (`array` of `string`s) names of
        attributes containing an URL. Default: `[ ]` (empty array).
    - **mode**: instructs **Linkchecker** to look for absolute (`'abs'`, default),
        relative (`'rel'`) URLs, or both (`'both'`).
  
## Other options ##

The **Linkchecker** module takes a few more options:

- **greenlist**: `array` of URLs **Linkchecker** will neglect. These may be 
  literal strings, or 
  [regular expressions](https://www.php.net/manual/en/reference.pcre.pattern.syntax.php) 
  (PCRE) without delimiters. Default: `[ ]` (empty array).
- **maxRequests**: `int`, maximum number of requests (access attempts) handled 
  simultaneously. This may be lowered to save CPU-cycles. Default: `40`.
- **timeout**: `int`, timeout of requests in milliseconds. Default: `3000`.
- **curlOptions**: `array` of extra options to be used in Curl requests
  There are [lots](https://www.php.net/manual/en/function.curl-setopt.php) of them.
  Please, use this option with care (or rather: not at all). 
  Default: `[ ]` (empty array).
- **tableName**: `string`, name of the **Linkchecker** database table. By default,
  the table is named `'linkchecker'`.
  
## HTTP Errors ##

Notice that not all errors reported by **Linkchecker** may be severe. For several
reasons, web sites may report `400 Bad Request` or `403 Forbidden` while working
perfectly normal in practice. *Spotify*, for instance, always seems to respond
with `400 Bad Request`.

This holds even more for redirection links. Often, `302 Found` is perfectly acceptable.
`301 Moved Permanently` or `301 TLS Redirect` may be a good reason to change the stored
URL (often involving nothing more than modifying the protocol from `http` to `https`),
but there are exceptions.
  
The opposite is also true. Receiving `200 OK` doesn't always really mean everything 
is OK. *YouTube*, for instance, always reports `200 OK`, even if the requested
video does not exist.

  


