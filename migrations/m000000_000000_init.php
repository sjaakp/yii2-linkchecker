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

namespace sjaakp\linkchecker\migrations;

use yii\db\Migration;
use sjaakp\linkchecker\Module;

/**
 * Class m000000_000000_init
 * @package sjaakp\linkchecker
 */
class m000000_000000_init extends Migration
{
    public function up()
    {
        $tableName = Module::getInstance()->tableName;

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable($tableName, [
            'id' => $this->primaryKey()->unsigned(),
            'url' => $this->string(280),
            'context' => $this->string(8)->null(),
            'source' => $this->string(60)->null(),
            'src_field' => $this->string(40)->null(),
            'src_pos' => $this->smallInteger()->null(),
            'status' => $this->string(40)->null(),
            'status_code' => $this->smallInteger()->null(),
            'location' => $this->string(280),
            'time' => $this->float(),
            'date' => $this->timestamp()->null(),
            'headers' => $this->text()->null(),
        ], $tableOptions);

        $this->createIndex('status_code', $tableName, 'status');
    }

    public function down()
    {
        $tableName = Module::getInstance()->tableName;
        $this->dropTable($tableName);
    }
}
