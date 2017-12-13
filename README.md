# Простой PHP-класс для отправки email через API Unisender

Полный пример использования - в файле test.php.
```
$sender = new SmsSender(array(
    'unisender_key' => '<key>',
    'sender' => 'Test'
));
```
Конструктор `SmsSender` принимает два аргумента:
- `$config` - ассоциативный массив с двумя значениями:
  - `unisender_key` - ключ API Unisender;
  - `sender` - имя отправителя;
- `$logger` - (опционально) логгер, реализующий [PSR-3 LoggerInterface](https://github.com/php-fig/log/blob/master/Psr/Log/LoggerInterface.php)

Метод только один: `sendSMS($to, $msg)`
- `$to` - номер, куда слать (может быть строкой, лишние символы удаляются);
- `$message` - текст отправляемого сообщения.
