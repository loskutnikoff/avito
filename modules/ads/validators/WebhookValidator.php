<?php

namespace app\modules\ads\validators;

class WebhookValidator
{
    private bool $isValid;
    private array $errors;

    private function __construct(bool $isValid, array $errors = [])
    {
        $this->isValid = $isValid;
        $this->errors = $errors;
    }

    public static function validateAvitoPayload(array $data): self
    {
        $errors = [];

        if (empty($data['id'])) {
            $errors[] = 'Отсутствует поле id';
        }

        if (!isset($data['timestamp']) || !is_numeric($data['timestamp'])) {
            $errors[] = 'Отсутствует или некорректное поле timestamp';
        }

        if (empty($data['payload'])) {
            $errors[] = 'Отсутствует поле payload';
        } else {
            $payload = $data['payload'];

            if (empty($payload['type'])) {
                $errors[] = 'Отсутствует поле payload.type';
            }

            if (empty($payload['value'])) {
                $errors[] = 'Отсутствует поле payload.value';
            } else {
                if (($payload['type'] ?? '') === 'message') {
                    $value = $payload['value'];

                    if (empty($value['id'])) {
                        $errors[] = 'Отсутствует поле payload.value.id';
                    }

                    if (empty($value['chat_id'])) {
                        $errors[] = 'Отсутствует поле payload.value.chat_id';
                    }

                    if (!isset($value['user_id']) || !is_numeric($value['user_id'])) {
                        $errors[] = 'Отсутствует или некорректное поле payload.value.user_id';
                    }

                    if (!isset($value['author_id']) || !is_numeric($value['author_id'])) {
                        $errors[] = 'Отсутствует или некорректное поле payload.value.author_id';
                    }
                }
            }
        }

        return new self(empty($errors), $errors);
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function getErrorMessage(): string
    {
        return implode('; ', $this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}