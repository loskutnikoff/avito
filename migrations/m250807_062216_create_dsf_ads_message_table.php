<?php

use yii\db\Migration;

class m250807_062216_create_dsf_ads_message_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('dsf_ads_message', [
            'id' => $this->primaryKey(),
            'chat_id' => $this->integer()->notNull(),
            'external_message_id' => $this->string(255)->notNull()->comment('ID сообщения во внешней системе'),
            'sender_type' => $this->string(50)->notNull()->comment('user, dealer, system'),
            'sender_id' => $this->string(255)->comment('ID отправителя во внешней системе'),
            'sender_name' => $this->string(255)->comment('Имя отправителя'),
            'message_type' => $this->string(50)->notNull()->defaultValue('text')->comment('text, image, file, etc'),
            'content' => $this->text()->notNull()->comment('Текст сообщения'),
            'is_read' => $this->boolean()->notNull()->defaultValue(false),
            'read_at' => $this->dateTime()->comment('Время прочтения'),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
            'created_by' => $this->integer(),
            'updated_by' => $this->integer(),
        ]);

        $this->createIndex('idx_ads_message_chat_id', 'dsf_ads_message', ['chat_id']);
        $this->createIndex('idx_ads_message_external_id', 'dsf_ads_message', ['external_message_id']);
        $this->createIndex('idx_ads_message_sender_type', 'dsf_ads_message', ['sender_type']);
        $this->createIndex('idx_ads_message_sender_id', 'dsf_ads_message', ['sender_id']);
        $this->createIndex('idx_ads_message_is_read', 'dsf_ads_message', ['is_read']);
        $this->createIndex('idx_ads_message_chat_created', 'dsf_ads_message', ['chat_id', 'created_at']);

        $this->addForeignKey(
            'fk_ads_message_chat_id',
            'dsf_ads_message',
            'chat_id',
            'dsf_ads_chat',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_ads_message_chat_id', 'dsf_ads_message');
        $this->dropTable('dsf_ads_message');
    }
}