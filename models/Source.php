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

use yii\db\ActiveRecord;

/**
 * This is the model class for a given table
 */
class Source extends ActiveRecord
{
    public static $table;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return self::$table;
    }
}
