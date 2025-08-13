<?php

namespace app\modules\ads\clients;

use app\modules\ads\models\DealerClassifier;
use app\modules\ads\models\Message;
use Exception;
use Yii;
use yii\httpclient\Client;

/**
 * API клиент для работы с Avito API v3
 *
 * @see https://developers.avito.ru/api-catalog
 */
class AvitoApiClient extends BaseApiClient
{
    public const API_URL = 'https://api.avito.ru';
    public const GRANT_TYPE = 'client_credentials';
    private const TOKEN_ENDPOINT = '/token/';
    private const WEBHOOK_ENDPOINT = '/messenger/v3/webhook';

    protected function getApiUrl(): string
    {
        return self::API_URL;
    }

    public function getAccessToken(DealerClassifier $classifier): ?string
    {
        try {
            $response = $this->httpClient->post($this->apiUrl . self::TOKEN_ENDPOINT)
                ->setData([
                    'grant_type' => self::GRANT_TYPE,
                    'client_id' => $classifier->client_id,
                    'client_secret' => $classifier->client_secret,
                ])
                ->setHeaders(['Content-Type' => 'application/x-www-form-urlencoded'])
                ->send();

            if ($response->isOk && isset($response->data['access_token'])) {
                Yii::info("Токен Авито получен для дилера {$classifier->dealer_id}", 'ads');

                return $response->data['access_token'];
            }

            Yii::error("Ошибка получения токена. HTTP: {$response->statusCode}, Response: {$response->content}", 'ads');

            return null;
        } catch (Exception $e) {
            Yii::error("Ошибка при получении токена: " . $e->getMessage(), 'ads');

            return null;
        }
    }

    public function subscribeWebhook(string $token, string $webhookUrl): bool
    {
        try {
            $requestData = [
                'url' => $webhookUrl,
                'events' => ['message.created'],
            ];

            Yii::info("Подписка на webhook. Token: " . substr($token, 0, 10) . "..., URL: {$webhookUrl}, Data: " . json_encode($requestData), 'ads');

            $response = $this->httpClient->post($this->apiUrl . self::WEBHOOK_ENDPOINT)
                ->setHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ])
                ->setFormat(Client::FORMAT_JSON)
                ->setData($requestData)
                ->send();

            if ($response->isOk) {
                Yii::info("Webhook подписка создана: {$webhookUrl}", 'ads');

                return true;
            }

            Yii::error(
                "Ошибка подписки на webhook. HTTP: {$response->statusCode}, Response: {$response->content}, Headers: " . json_encode($response->headers),
                'ads'
            );

            return false;
        } catch (Exception $e) {
            Yii::error("Ошибка при подписке на webhook: " . $e->getMessage(), 'ads');

            return false;
        }
    }

    public function getWebhooks(string $token): ?array
    {
        try {
            $response = $this->httpClient->post($this->apiUrl . '/messenger/v1/subscriptions')
                ->setHeaders(['Authorization' => 'Bearer ' . $token])
                ->send();

            if ($response->isOk) {
                return $response->data;
            }

            Yii::error("Ошибка получения webhook. HTTP: {$response->statusCode}, Response: {$response->content}", 'ads');

            return null;
        } catch (Exception $e) {
            Yii::error("Ошибка при получении webhook: " . $e->getMessage(), 'ads');

            return null;
        }
    }

    public function unsubscribeWebhook(string $token, string $webhookUrl): bool
    {
        try {
            $requestData = [
                'url' => $webhookUrl,
            ];

            $response = $this->httpClient->post($this->apiUrl . "/messenger/v1/webhook/unsubscribe")
                ->setHeaders(['Authorization' => 'Bearer ' . $token])
                ->setFormat(Client::FORMAT_JSON)
                ->setData($requestData)
                ->send();

            if ($response->isOk) {
                Yii::info("Webhook отписка удалена: {$webhookUrl}", 'ads');

                return true;
            }

            Yii::error("Ошибка отписки от webhook. HTTP: {$response->statusCode}, Response: {$response->content}", 'ads');

            return false;
        } catch (Exception $e) {
            Yii::error("Ошибка при отписке от webhook: " . $e->getMessage(), 'ads');

            return false;
        }
    }

    public function getChatData(string $token, int $userId, string $chatId): ?array
    {
        try {
            $response = $this->httpClient->get($this->apiUrl . "/messenger/v2/accounts/{$userId}/chats/{$chatId}")
                ->setHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->send();

            if ($response->isOk && isset($response->data)) {
                Yii::info("Данные чата {$chatId} получены", 'ads');

                return $response->data;
            }
            Yii::error("Ошибка получения данных чата. HTTP: {$response->statusCode}, Response: {$response->content}", 'ads');

            return null;
        } catch (Exception $e) {
            Yii::error("Ошибка при получении данных чата: " . $e->getMessage(), 'ads');

            return null;
        }
    }

    public function getMessagesData(string $token, int $userId, string $chatId): ?array
    {
        try {
            $response = $this->httpClient->get($this->apiUrl . "/messenger/v3/accounts/{$userId}/chats/{$chatId}/messages")
                ->setHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])
                ->send();

            if ($response->isOk && isset($response->data)) {
                Yii::info("Данные чата {$chatId} получены", 'ads');

                return $response->data;
            }
            Yii::error("Ошибка получения данных чата. HTTP: {$response->statusCode}, Response: {$response->content}", 'ads');

            return null;
        } catch (Exception $e) {
            Yii::error("Ошибка при получении данных чата: " . $e->getMessage(), 'ads');

            return null;
        }
    }

    public function sendMessage(string $token, int $userId, string $externalChatId, string $message): ?array
    {
        try {
            $response = $this->httpClient->post($this->apiUrl . "/messenger/v1/accounts/{$userId}/chats/{$externalChatId}/messages")
                ->setHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ])
                ->setFormat(Client::FORMAT_JSON)
                ->setData(['message' => ['text' => $message], 'type' => Message::MESSAGE_TYPE_TEXT])
                ->send();

            if ($response->isOk) {
                Yii::info("Сообщение отправлено в чат {$externalChatId}", 'ads');

                return $response->data;
            }

            Yii::error("Ошибка отправки сообщения. HTTP: {$response->statusCode}, Response: {$response->content}", 'ads');

            return null;
        } catch (Exception $e) {
            Yii::error("Ошибка при отправке сообщения: " . $e->getMessage(), 'ads');

            return null;
        }
    }
}
