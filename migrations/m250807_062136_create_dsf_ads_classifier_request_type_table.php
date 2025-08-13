<?php

use yii\db\Migration;

class m250807_062136_create_dsf_ads_classifier_request_type_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('dsf_ads_classifier_request_type', [
            'id' => $this->primaryKey(),
            'platform_type' => $this->integer()->notNull(),
            'source_id' => $this->integer()->notNull(),
            'request_type_id' => $this->integer()->notNull(),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
            'created_by' => $this->integer(),
            'updated_by' => $this->integer(),
        ]);

        $this->createIndex('idx_ads_classifier_request_type_platform', 'dsf_ads_classifier_request_type', 'platform_type');
        $this->createIndex('idx_ads_classifier_request_type_request_type', 'dsf_ads_classifier_request_type', 'request_type_id');
        $this->createIndex('idx_ads_classifier_request_type_source', 'dsf_ads_classifier_request_type', 'source_id');
        $this->createIndex('idx_ads_classifier_request_type_unique', 'dsf_ads_classifier_request_type', ['platform_type', 'request_type_id'], true);

        $this->addForeignKey(
            'fk_ads_classifier_request_type_request_type_id',
            'dsf_ads_classifier_request_type',
            'request_type_id',
            'lms_request_type',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_ads_classifier_request_type_source_id',
            'dsf_ads_classifier_request_type',
            'source_id',
            'dsf_source',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_ads_classifier_request_type_source_id', 'dsf_ads_classifier_request_type');
        $this->dropForeignKey('fk_ads_classifier_request_type_request_type_id', 'dsf_ads_classifier_request_type');
        $this->dropIndex('idx_ads_classifier_request_type_unique', 'dsf_ads_classifier_request_type');
        $this->dropIndex('idx_ads_classifier_request_type_request_type', 'dsf_ads_classifier_request_type');
        $this->dropIndex('idx_ads_classifier_request_type_platform', 'dsf_ads_classifier_request_type');
        $this->dropTable('dsf_ads_classifier_request_type');
    }
}