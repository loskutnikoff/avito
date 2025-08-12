<?php

namespace app\modules\ads\exceptions;

class MessageCreationException extends \Exception
{
    public function __construct(array $errors, string $message = 'Ошибка создания сообщения', int $code = 0, \Throwable $previous = null)
    {
        $errorMessage = $message . ': ' . json_encode($errors, JSON_UNESCAPED_UNICODE);
        parent::__construct($errorMessage, $code, $previous);
    }
}
