<?php
/**
 * Bootstrap — инициализация фреймворка ZendLight
 */

namespace Core;

class Bootstrap
{
    public static function init()
    {	
        // Установка часового пояса
        $config = include __DIR__ . '/../config/config.php';
        date_default_timezone_set($config['timezone'] ?? 'UTC');

        // Показ ошибок
        if (!empty($config['display_errors'])) {
            ini_set('display_errors', 1);
            error_reporting(E_ALL);
        }
    }
}
