<?php

use yii\db\Migration;

class m250807_062106_create_dsf_ads_chat_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('dsf_ads_chat', [
            'id' => $this->primaryKey(),
            'advert_id' => $this->integer()->notNull(),
            'interest_id' => $this->integer(),
            'external_chat_id' => $this->string(255)->notNull()->comment('ID чата во внешней системе'),
            'external_user_id' => $this->string(255)->comment('ID пользователя во внешней системе'),
            'user_name' => $this->string(255)->comment('Имя пользователя'),
            'external_author_id' => $this->string(255)->comment('ID автора/продавца во внешней системе'),
            'author_name' => $this->string(255)->comment('Имя автора/продавца'),
            'status' => $this->string(50)->notNull()->defaultValue('active')->comment('active, closed, archived'),
            'last_message_at' => $this->dateTime()->comment('Время последнего сообщения'),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
            'created_by' => $this->integer(),
            'updated_by' => $this->integer(),
        ]);

        $this->createIndex('idx_ads_chat_advert_id', 'dsf_ads_chat', ['advert_id']);
        $this->createIndex('idx_ads_chat_interest_id', 'dsf_ads_chat', ['interest_id']);
        $this->createIndex('idx_ads_chat_external_chat_id', 'dsf_ads_chat', ['external_chat_id']);
        $this->createIndex('idx_ads_chat_external_user_id', 'dsf_ads_chat', ['external_user_id']);
        $this->createIndex('idx_ads_chat_external_author_id', 'dsf_ads_chat', ['external_author_id']);
        $this->createIndex('idx_ads_chat_status', 'dsf_ads_chat', ['status']);
        $this->createIndex('idx_ads_chat_last_message', 'dsf_ads_chat', ['last_message_at']);

        $this->addForeignKey(
            'fk_ads_chat_advert_id',
            'dsf_ads_chat',
            'advert_id',
            'dsf_ads_advert',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_ads_chat_interest_id',
            'dsf_ads_chat',
            'interest_id',
            'lms_interest',
            'id',
            'SET NULL',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_ads_chat_interest_id', 'dsf_ads_chat');
        $this->dropForeignKey('fk_ads_chat_advert_id', 'dsf_ads_chat');
        $this->dropTable('dsf_ads_chat');
    }
}