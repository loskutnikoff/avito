# Модуль ADS (Рекламные платформы)

Модуль для интеграции с рекламными платформами (Avito, Auto.ru) для автоматического сбора лидов из мессенджеров в LMS.

### Поддерживаемые платформы
- **Avito** - готово полностью
- **AutoRu** - заглушка

## Архитектура

### Основные компоненты

```
modules/ads/
├── clients/                             # API компоненты
│   ├── BaseApiClient                    # Базовый HTTP клиент
│   ├── AvitoApiClient                   # Клиент для Avito API
│   └── AutoruApiClient                  # Заглушка для Auto.ru API
├── ️ commands/                            # Консольные команды
│   ├── TestingController                # Тестирование функционала
│   └── ClassifierController             # Управление интеграциями через cron
├── components/                          # Компоненты платформ
│   ├── BasePlatform                     # Базовый класс для всех платформ
│   ├── AvitoPlatform                    # Реализация для Avito
│   ├── AutoruPlatform                   # Заглушка для Auto.ru
│   └── PlatformInterface                # Интерфейс платформ
├── controllers/                         # Контроллеры
│   ├── WebhookController                # Обработка вебхуков от платформ
│   └── ClassifierRequestTypeController  # Управление справочником платформ
├── dto/                                 # DTO
│   ├── AvitoWebhookDto                  # Парсинг вебхуков Avito
│   ├── AutoruWebhookDto                 # Парсинг вебхуков Auto.ru
│   ├── LeadDataDto                      # DTO данных для лида
│   ├── MessageContentDto                # DTO данных контента сообщения
│   └── MessageDto                       # DTO данных сообщения
├── exceptions/                          # Объекты передачи данных
│   ├── AdvertCreationException          # Exception для объявления
│   ├── ChatCreationException            # Exception для чата
│   └── MessageCreationException         # Exception для сообщения
├── factories/                           # Фабрики
│   ├── PlatformFactory                  # Фабрика платформ
├── helpers/                             # Хелперы
│   ├── WebhookLogic                     # Бизнес логика вебхуков
│   ├── WebhookManager                   # Хелпер для управления вебхуками
├── interfaces/                          # Интерфейсы сервисов
│   ├── AdvertServiceInterface
│   ├── ChatServiceInterface
│   ├── MessageServiceInterface
│   ├── InterestInterface
├── jobs/                                # Очереди
│   ├── ProcessAvitoWebhookJob           # Обработка вебхуков Avito
├── models/                              # Модели
│   ├── DealerClassifier                 # Настройки интеграций дилеров
│   ├── Advert                           # Объявления с платформ
│   ├── Chat                             # Чаты пользователей
│   ├── Message                          # Сообщения в чатах
│   ├── ClassifierRequestType            # Справочник платформ для request_type_id и source_id
├── services/                            # Сервисы бизнес-логики
│   ├── InterestService                  # Создание обращений в LMS
│   ├── AdvertService                    # Управление объявлениями
│   ├── ChatService                      # Управление чатами
│   ├── MessageService                   # Управление сообщениями
│   └── TokenManager                     # Кеширование и управление токенами доступа
├── validators/                          # Валидаторы данных
    └── WebhookValidator                 # Валидатор данных с вебхука
```

### Безопасность

- **Webhook токены**: Каждый классификатор имеет уникальный `webhook_token` для валидации
- **URL маршрутизация**: `?classifier_id=X&token=Y` в webhook URL
- **OAuth 2.0**: Автоматическое получение и обновление токенов доступа

### Инструкция по старту

1. Переходим в [Avito для бизнеса](https://developers.avito.ru/)
2. Создаем приложение в [личном кабинете](https://developers.avito.ru/applications)
3. Получаем `client_id` и `client_secret`
4. Указываем домен для webhook'ов (redirect_url)
5. Каждый дилер регистрирует свое приложение, получает client_id и client_secret
6. Создаем в справочнике запись `url:/ads/classifier-request-type/index`, уникальность записей по площадке
7. Переходим в карточку дилера, во вкладке **"Служебные настройки"** -> **"Интеграции с рекламными платформами"** вводим `client_id` и `client_secret`, нажимаем `+` и сохраняем форму
8. В нашей системе у него генерируется свой токен и регистрируется вебхук по адресу `redirect_url?ads/webhook/{platform_name}/classifier_id=X&token=Y`

**Система автоматически:**
- Получит токен доступа от Avito
- Сгенерирует webhook токен
- Подпишется на уведомления

### Как работает DealerForm
1. При добавление новой платформы с актуальными client_id и client_secret происходит регистрация токена в Avito и генерация токена в нашей системе, далее подписка на вебхук по индивидуальному url
2. При редактирование платформы сравниваются данные, если они отличаются, то происходит отписка от вебхука и снова подписка
3. При удаление платформы происходит отписка от вебхука и сброс токена в нашей системе

### Webhook URL

Система автоматически сгенерирует URL вида:
```
https://your-domain.com/ads/webhook/avito?classifier_id=2&token=abc123...
```

## Использование

### Создание лидов

При получении сообщения в чат на Avito:

1. **Webhook** приходит на `/ads/webhook/avito`
2. **Проверяется** `classifier_id` и `token` (наш токен системный)
3. **Создается/обновляется**:
    - `Advert` (объявление)
    - `Chat` (чат с пользователем)
    - `Message` (сообщение)
4. **Создается** `Interest` (обращение в LMS) только для новых чатов и если написал покупатель
5. **Автоматически создается** `Client` и `Request`

### Структура данных Avito API v3

#### Когда приходит Webhook
```json
{
    "id": "c061cb80-2ff3-4bf9-a9ea-6cc6418dbfbd",
    "version": "v3.0.0",
    "timestamp": 1754916287,
    "payload": {
        "type": "message",
        "value": {
            "id": "e1db89671023344e771c480b69a48f4f",
            "chat_id": "u2i-CuzqKIaZ9_mERi0AEbfPig",
            "user_id": 161605962,
            "author_id": 350687742,
            "created": 1754916284,
            "type": "text",
            "chat_type": "u2i",
            "content": {
                "text": "123"
            },
            "item_id": 7463793992,
            "published_at": "2025-08-11T12:44:44Z"
        }
    }
}
```
#### Когда получаем данные по чату
```json
{
    "id": "u2i-CuzqKIaZ9_mERi0AEbfPig",
    "context": {
        "type": "item",
        "value": {
            "id": 7463793992,
            "title": "Детский автомобиль электрический",
            "user_id": 161605962,
            "images": {
                "main": {
                    "140x105": "https://80.img.avito.st/image/1/1.kXOWibaxPZq4KO2ZuJ_fUvQoP5okPDmY.7x3LWEEcn1mvaycatG0No07wgNWPyY0GFDZaTBc2o7c"
                },
                "count": 1
            },
            "status_id": 4,
            "price_string": "55 000 ₽",
            "url": "https://avito.ru/ekaterinburg/tovary_dlya_detey_i_igrushki/detskiy_avtomobil_elektricheskiy_7463793992",
            "location": {
                "title": "Екатеринбург",
                "lat": 56.837716,
                "lon": 60.596828
            }
        }
    },
    "created": 1754910065,
    "updated": 1754916284,
    "users": [
        {
            "id": 350687742,
            "name": "Артем",
            "parsing_allowed": false,
            "public_user_profile": {
                "user_id": 350687742,
                "item_id": 7463793992,
                "avatar": {
                    "default": "https://static.avito.ru/stub_avatars/%D0%90/3_256x256.png",
                    "images": {
                        "128x128": "https://static.avito.ru/stub_avatars/%D0%90/3_128x128.png",
                        "192x192": "https://static.avito.ru/stub_avatars/%D0%90/3_192x192.png",
                        "24x24": "https://static.avito.ru/stub_avatars/%D0%90/3_24x24.png",
                        "256x256": "https://static.avito.ru/stub_avatars/%D0%90/3_256x256.png",
                        "36x36": "https://static.avito.ru/stub_avatars/%D0%90/3_36x36.png",
                        "48x48": "https://static.avito.ru/stub_avatars/%D0%90/3_48x48.png",
                        "64x64": "https://static.avito.ru/stub_avatars/%D0%90/3_64x64.png",
                        "72x72": "https://static.avito.ru/stub_avatars/%D0%90/3_72x72.png",
                        "96x96": "https://static.avito.ru/stub_avatars/%D0%90/3_96x96.png"
                    }
                },
                "url": "https://avito.ru/user/3ea0f7500eaac80c772ee752f208974a/profile?iid=7463793992&page_from=from_item_messenger&src=messenger&id=7463793992"
            }
        },
        {
            "id": 161605962,
            "name": "Гена",
            "parsing_allowed": true,
            "public_user_profile": {
                "user_id": 161605962,
                "item_id": 7463793992,
                "avatar": {
                    "default": "https://static.avito.ru/stub_avatars/%D0%93/7_256x256.png",
                    "images": {
                        "128x128": "https://static.avito.ru/stub_avatars/%D0%93/7_128x128.png",
                        "192x192": "https://static.avito.ru/stub_avatars/%D0%93/7_192x192.png",
                        "24x24": "https://static.avito.ru/stub_avatars/%D0%93/7_24x24.png",
                        "256x256": "https://static.avito.ru/stub_avatars/%D0%93/7_256x256.png",
                        "36x36": "https://static.avito.ru/stub_avatars/%D0%93/7_36x36.png",
                        "48x48": "https://static.avito.ru/stub_avatars/%D0%93/7_48x48.png",
                        "64x64": "https://static.avito.ru/stub_avatars/%D0%93/7_64x64.png",
                        "72x72": "https://static.avito.ru/stub_avatars/%D0%93/7_72x72.png",
                        "96x96": "https://static.avito.ru/stub_avatars/%D0%93/7_96x96.png"
                    }
                },
                "url": "https://avito.ru/user/59b0b03a4149dc644f18431a2e224c92/profile?iid=7463793992&page_from=from_item_messenger&src=messenger&id=7463793992"
            }
        }
    ],
    "last_message": {
        "id": "e1db89671023344e771c480b69a48f4f",
        "author_id": 350687742,
        "created": 1754916284,
        "content": {
            "text": "123"
        },
        "type": "text",
        "direction": "in",
        "delivered": 1754916284
    }
}
```


## Консольные команды

### Тестирование

```bash
# Справка по командам
php yii ads/testing/help
php yii ads/classifier/help

# Тестирование webhook'а Avito
php yii ads/testing/test-avito-webhook <dealer_id> <classifier_id>

# Тестирование получения токена
php yii ads/testing/test-avito-token <dealer_id> <classifier_id>

# Информация о классификаторе
php yii ads/testing/info <classifier_id>

# Статус всех классификаторов
php yii ads/classifier/status
```

### Управление

```bash
# Проверка и регистрация webhook'ов
php yii ads/classifier/check-webhooks

# Проверка токенов доступа
php yii ads/classifier/check-tokens

# Валидация учетных данных
php yii ads/classifier/validate-credentials

# Генерация нового webhook токена в нашей системе
php yii ads/classifier/generate-webhook-token <classifier_id>
```

## Cron задачи

### Рекомендуемая настройка

```bash
# Проверка токенов каждый час
0 * * * * /usr/bin/php /path/to/project/yii ads/classifier/check-tokens

# Проверка и регистрация webhook'ов каждые 6 часов
0 */6 * * * /usr/bin/php /path/to/project/yii ads/classifier/check-webhooks

# Проверка учетных данных раз в день в 8 утра
0 8 * * * /usr/bin/php /path/to/project/yii ads/classifier/validate-credentials
```

### Логика управления через cron

**check-tokens** (каждый час):
- Проверяет валидность токенов доступа
- Автоматически обновляет истекшие токены
- Логирует ошибки авторизации

**check-webhooks** (каждые 6 часов):
- Генерирует `webhook_token` если отсутствует
- Получает список подписок через API
- Регистрирует новые webhook'и при необходимости
- Проверяет корректность существующих подписок

**validate-credentials** (по требованию):
- Проверяет правильность `client_id` и `client_secret`
- Выявляет неактивные или недействительные интеграции