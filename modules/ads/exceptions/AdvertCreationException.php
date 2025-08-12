<?php

namespace app\modules\ads\exceptions;

class AdvertCreationException extends \Exception
{
    public function __construct(array $errors, string $message = 'Ошибка создания объявления', int $code = 0, \Throwable $previous = null)
    {
        $errorMessage = $message . ': ' . json_encode($errors, JSON_UNESCAPED_UNICODE);
        parent::__construct($errorMessage, $code, $previous);
    }
}
