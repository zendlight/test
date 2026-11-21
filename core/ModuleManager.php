<?php
namespace Core;
/**
 * ModuleManager — обнаруживает и загружает модули из /module/.
 */
class ModuleManager
{
    private string $modulesPath;
    protected array $configs = [];
	protected array $executed = [];
	protected array $loadedConfigs = [];

    public function __construct(string $modulesPath = null)
    {
        // путь к папке module (по умолчанию ../module)
        $this->modulesPath = $modulesPath ?? __DIR__ . '/../module';
    }
#------------------------------------------------------
/*
	загружается сразу после определеения модуля в роутере
	из глобольного \config\config.php
*/
    public function load(string $module): void
    {
#	echo '<br>'.__METHOD__.' : '.__LINE__.'<br>';	
	
#echo '<pre>';
#$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
#print_r($backtrace);
#echo '</pre>';

		// Protection от повторной загрузки
        if (isset($this->loadedConfigs[$module])) {	
            return;
        }

	$configPath = $this->modulesPath."/$module/config/module.php";	
		
#	echo '$configPath = ' . $configPath .'<br>';		
		
		
        if (file_exists($configPath)) {			
            $this->configs[$module] = require $configPath;
        }

        $this->loadedConfigs[$module] = true;
    }
#------------------------------------------------------
    /**
     * Ленивая загрузка модуля
	 * вызывается ввнутри функции /core/Router->dispatch(string $uri);
	 * исполняется после определеения Config и action
     */
public function execute(string $moduleName)
{
#	echo '<br>'.__METHOD__.' : '.__LINE__.'<br>';

	if (isset($this->executed[$moduleName])) {
		return;
	}	
	
    $moduleClass = "\\{$moduleName}\\Module";
	$modulePath = $this->modulesPath."/{$moduleName}/Module.php";	
	
    if (file_exists($modulePath)) {
        require_once $modulePath;
        if (class_exists($moduleClass)) {
            $module = new $moduleClass();

            // Вызов onBootstrap() если есть
            if (method_exists($module, 'onBootstrap')) {
                $module->onBootstrap();
            }
        }
    }
//	каждый модуль сам “подключает” свои переводы:		
		\Core\Translator::loadModule($moduleName);	

	$this->executed[$moduleName] = true;	
}
#------------------------------------------------------

    public function getConfig(string $module): array
    {
        return $this->configs[$module] ?? [];
    }
}