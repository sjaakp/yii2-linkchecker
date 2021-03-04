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

namespace sjaakp\linkchecker\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;
use sjaakp\linkchecker\Module;

/**
 * This is the model class for table "checkedlink".
 *
 * @property int $id
 * @property string $url
 * @property string $context
 * @property string $source
 * @property string $src_field
 * @property int $src_pos
 * @property string $status
 * @property int $status_code
 * @property string $location
 * @property float $time
 * @property string $date
 * @property string $headers
 */

class Link extends ActiveRecord
{
    public static $_tableName;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return self::$_tableName;
    }

    protected static function getPattern($mode = 'abs')
    {
        $patterns = [
            'both' => '.+',
            'rel' => '/?[^/][^:]+',
        ];
        return $patterns[$mode] ?? '(?:https?:)?//.+';
    }

    protected static function getModels($modelClass, $where = [])
    {
        if (strpos($modelClass, '\\') === false) {  // no \, assume $modelClass is a table name
            Source::$table = $modelClass;
            $modelClass = Source::class;
        }
        return $modelClass::find()->where($where)->all();
    }

    protected static function distillLinks($model, $attribute, $mode = 'abs')
    {
        $pattern = self::getPattern($mode);

        $text = $model->getAttribute($attribute);
        $matches = [];
        preg_match_all("^(href|src|srcset)\s*=\s*(['\"])($pattern)\g2^U", $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        /* Frankly, handling of srcset is not perfect.
         * It may malfunction if:
         * - the urls are of mixed abs and rel modes
         * - one or more of the urls contain a comma
         * These are very exotic conditions, so for now I'm leaving it as it is.
         * https://stackoverflow.com/questions/198606/can-i-use-commas-in-a-url
         */
        foreach ($matches as $match) {
            $context = $match[1][0];
            if ($context == 'srcset')   {
                $urls = array_map(function ($v) {
                    $tr = ltrim($v);
                    return substr($tr, 0, strcspn($tr, ' '));
                }, explode(',', $match[3][0]));
            }
            else {
                $urls = [$match[3][0]];
            }
            foreach ($urls as $url) {
                $link = new Link([
                    'url' => $url,
                    'context' => $context,
                    'src_pos' => $match[3][1],
//                    'src_model' => get_class($model),
                    'source' => self::route($model),
                    'src_field' => $attribute,
                ]);
                $link->save();
            }
        }
        return [];
    }

    public static function clearLinks()
    {
        self::getDb()->createCommand()->truncateTable(self::tableName())->execute();    // clear previous links
    }

    public static function getLinks($modelClass, $attributes, $where = [], $mode = 'abs')
    {
        if (is_string($attributes)) $attributes = [ $attributes ];

        $models = self::getModels($modelClass, $where);
        $pattern = self::getPattern($mode);

        foreach ($models as $model) {
            foreach ($attributes as $attr) {
                $url = $model->getAttribute($attr);
                if ($url && preg_match("^$pattern^", $url)) {
                    $link = new Link([
                        'url' => $url,
//                        'src_model' => get_class($model),
                        'source' => self::route($model),
                        'src_field' => $attr,
                    ]);
                    $link->save();
                }
            }
        }
    }

    public static function collectLinks($modelClass, $attributes, $where = [], $mode = 'abs')
    {
        if (is_string($attributes)) $attributes = [ $attributes ];
        $models = self::getModels($modelClass, $where);

        foreach ($models as $model) {
            foreach ($attributes as $attr) {
                self::distillLinks($model, $attr, $mode);
            }
        }
    }

    public static function scanLinks()
    {
        $module = Module::getInstance();

        $max_requests = $module->maxRequests;

        $requests_map = [];
        $multi_handle = curl_multi_init();

        $links = self::find()->all();
        $count = count($links);

        for ($i = 0; $i < min($max_requests, $count); $i++)   {
            $curl = curl_init();
            $link = $links[$i];
            $link->request($curl);
            curl_multi_add_handle($multi_handle, $curl);
            $request_map[intval($curl)] = $link;
        }

        $active = 0;

        // Weird code, but this appears to be the way to do it
//         https://stackoverflow.com/questions/19490837/curlm-call-multi-perform-deprecated
        do{
            do{
                $mrc = curl_multi_exec($multi_handle, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);

            while ($completed = curl_multi_info_read($multi_handle)) {
                $curl = $completed['handle'];   // request completed
                $idx = intval($curl);
                $link = $request_map[$idx];
                $link->process($curl);
                curl_multi_remove_handle($multi_handle, $curl);

                if ($i < $count)    {   // we have more links, reuse $curl
                    $link = $links[$i];
                    $link->request($curl);
                    curl_multi_add_handle($multi_handle, $curl);
                    $request_map[$idx] = $link;
                    $i++;
                }
                else    {
                    curl_close($curl);
                }
            }
            if (curl_multi_select($multi_handle) == -1) {
                usleep(15);
            }
        } while ($active && $mrc == CURLM_OK);

        curl_multi_close($multi_handle);
    }

    public static function route($model)
    {
        return Yii::$app->urlManager->createUrl([ "{$model->tableName()}/view", 'id' => $model->primaryKey ]);
    }

    public function request($curl)
    {
        $module = Module::getInstance();

        $secure = Yii::$app->request->isSecureConnection;

        $url = $this->url;
        if (substr($url, 0, 2) == '//') {    // curl doesn't take scheme-relative URL
            $scheme = $secure ? 'https:' : 'http:';
            $url = $scheme . $url;
        }
        $opts = array_replace([
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT_MS => $module->timeout,
            CURLOPT_SSL_VERIFYPEER => $secure
        ], $module->curlOptions, [
            CURLOPT_URL => $url,
        ]);
        curl_setopt_array($curl, $opts);
    }

    public function process($curl)
    {
        $err = curl_errno($curl);
        if ($err)   {   // we have an error
            // friendly text for some common errors @link https://curl.se/libcurl/c/libcurl-errors.html
            $errs = [
                CURLE_URL_MALFORMAT =>  Yii::t('linkchecker', 'URL malformed'),
                CURLE_COULDNT_CONNECT => Yii::t('linkchecker', 'Couldn\'t connect'),
                CURLE_OPERATION_TIMEOUTED => Yii::t('linkchecker', 'Timed out')
            ];

            $this->status = $errs[$err] ?? curl_error($curl);
            $this->headers = curl_strerror($curl);
        }
        else {  // CURLE_OK
            $info = curl_getinfo($curl);
            $this->headers = $headers = curl_multi_getcontent($curl);   // HEAD request, so all content is headers
            $this->status = strstr($headers, "\r\n", true); // first line
            $this->status_code = $info['http_code'];
            $this->location = $info['redirect_url'];
            $this->time = $info['total_time'];
        }
        $this->date = new Expression('NOW()');
        $this->save(false);
    }

    /**
      * {@inheritdoc}
     *  Ensure url is unique to avoid multiple tries
     */
    public function rules()
    {
        return [
            ['url', 'unique'],
            ['url', 'validateGreenlist']
        ];
    }

    public function validateGreenlist($attribute, $params, $validator)
    {
        $module = Module::getInstance();

        foreach ($module->greenlist as $green)  {
            if (preg_match("~$green~", $this->getAttribute($attribute))) {
                $this->addError($attribute, 'In greenlist');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'url' => 'Url',
            'context' => 'Context',
            'src_model' => 'Source Model',
            'src_field' => 'Source Field',
            'src_pos' => 'Source Position',
            'status' => 'Status',
            'status_code' => 'Status Code',
            'location' => 'Redirected to',
            'time' => 'Time (sec)',
            'date' => 'Scanned at',
            'headers' => 'Headers',
        ];
    }
}
