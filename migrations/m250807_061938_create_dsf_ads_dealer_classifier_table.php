<?php

use yii\db\Migration;

class m250807_061938_create_dsf_ads_dealer_classifier_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('dsf_ads_dealer_classifier', [
            'id' => $this->primaryKey(),
            'dealer_id' => $this->integer()->unsigned()->notNull(),
            'type' => $this->smallInteger()->notNull(),
            'client_id' => $this->string(255)->notNull(),
            'client_secret' => $this->string(255)->notNull(),
            'webhook_token' => $this->string(255)->null()->comment('Токен для валидации вебхуков'),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
            'created_by' => $this->integer(),
            'updated_by' => $this->integer(),
        ]);

        $this->createIndex('idx_ads_dealer_classifier_dealer_type', 'dsf_ads_dealer_classifier', ['dealer_id', 'type']);
        $this->createIndex('idx_ads_dealer_classifier_type_active', 'dsf_ads_dealer_classifier', ['type', 'is_active']);
        $this->createIndex('idx_ads_dealer_classifier_client_id', 'dsf_ads_dealer_classifier', ['client_id']);

        $this->addForeignKey(
            'fk_ads_dealer_classifier_dealer_id',
            'dsf_ads_dealer_classifier',
            'dealer_id',
            'dealers',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_ads_dealer_classifier_dealer_id', 'dsf_ads_dealer_classifier');
        $this->dropTable('dsf_ads_dealer_classifier');
    }
}