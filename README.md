# URL-BD-CLEANER

![npm](https://img.shields.io/badge/php-7.2-blue)
![mysql](https://img.shields.io/badge/mysql-5.7-green)
![composer](https://img.shields.io/badge/composer-2.1-red)


## Project using

### Description
There is the unique product in front on you.
The world won't be the same again. Welcome! The script which replace one phrase to another in you mysql Database. Just point two words into console string and script will do everything for you.


### Installing

* `cd /path/to/project/with/package/json/on/root`

* 
```shell
>> composer require slovenberg/url-db-cleaner
```

### Using

* You should have next standard variables in PHP environment:
__DB_HOST__, __DB_NAME__, __DB_USER__, __DB_PASSWORD__.


* There is only one public static method which can start the main process: ___start___.
Insert it into your script like:
```php
use slovenberg\WpDbCleaner\Main;

Main::start();
```
or use short version:
```php
slovenberg\WpDbCleaner\Main::start();
```

* Run your script by console like:
```shell
>> php your_script.php old_word new_word
```

