<?php
namespace Core;
/**
 * Config — утилиты загрузки конфигов.
 */
class Config
{
    public static function loadFile(string $path): array
    {
        if (!file_exists($path)) return [];
        $cfg = include $path;
        return is_array($cfg) ? $cfg : [];
    }
	
//---------------------------------------------------------
    public static function merge(array ...$configs): array
    {
        $result = [];
        foreach ($configs as $cfg) {
            $result = array_merge($result, $cfg);
        }
        return $result;
    }
	
//---------------------------------------------------------	
	public static function get(string $key, $default = null)
	{
		static $config;

		if (!$config) {
			$config = require __DIR__ . '/../config/config.php';
		}

		return $config[$key] ?? $default;
	}
}
