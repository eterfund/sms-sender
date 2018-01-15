# Простой PHP-класс для отправки email через API Unisender

Полный пример использования - в файле test.php.

Конструктор `SmsSender` принимает два аргумента:
- `unisender_key` - ключ API Unisender;
- `$logger` - (опционально) логгер, реализующий [PSR-3 LoggerInterface](https://github.com/php-fig/log/blob/master/Psr/Log/LoggerInterface.php)

Метод только один: `sendSMS($params)`
`$params` - ассоциативный массив с ключами:
- `phone` - номер, куда слать (может быть строкой, лишние символы удаляются);
- `sender` - имя отправителя;
- `text` - текст отправляемого сообщения.
Возвращает SMS ID отправленного сообщения.
В случае ошибки выбрасывает исключение.
