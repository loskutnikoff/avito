<?php

use yii\db\Migration;

class m250807_062024_create_dsf_ads_advertisement_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('dsf_ads_advert', [
            'id' => $this->primaryKey(),
            'classifier_id' => $this->integer()->notNull(),
            'external_id' => $this->string(255)->notNull()->comment('ID объявления во внешней системе'),
            'title' => $this->string(500)->notNull(),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
            'created_by' => $this->integer(),
            'updated_by' => $this->integer(),
        ]);

        $this->createIndex('idx_ads_advert_classifier_id', 'dsf_ads_advert', ['classifier_id']);
        $this->createIndex('idx_ads_advert_external_id', 'dsf_ads_advert', ['external_id']);
        $this->createIndex('idx_ads_advert_classifier_external', 'dsf_ads_advert', ['classifier_id', 'external_id']);

        $this->addForeignKey(
            'fk_ads_advert_classifier_id',
            'dsf_ads_advert',
            'classifier_id',
            'dsf_ads_dealer_classifier',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_ads_advert_classifier_id', 'dsf_ads_advert');
        $this->dropTable('dsf_ads_advert');
    }
}