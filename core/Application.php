<?php
namespace Core;
/**
 * Application — главный класс запуска приложения.
 * Принимает массив конфигурации (config/config.php) и запускает Router.
 */
 
use Core\Translator;
use Core\Logger;
use Core\Moderation\Visitor\VisitorManager;

class Application
{
    private array $config;
    private ModuleManager $moduleManager;
    protected Router $router;
    protected Logger $logger;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->moduleManager = new ModuleManager();	
        // 👉 режим
        $env = $config['env'] ?? 'prod';
		
#	echo '$env = ' . $env . '<br>';		

        if ($env === 'dev') {
            ini_set('display_errors', 1);
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', 0);
            error_reporting(0);
        }

        $this->logger = new Logger($config['log']['level'] ?? 'error'
        );		
		
		$lang = $_SESSION['lang'] ?? $config['languages']['default'];
		Translator::init($lang);
		
//23.05.2026:	
		$visitorManager = new VisitorManager();
		$_SESSION['_visitor'] = $visitorManager->detect();	
/*	
	Теперь visitor доступен везде:
	$_SESSION['_visitor']->id
	Например:
	echo $_SESSION['_visitor']->id;
*/	
	}
//-----------------------------------------------------
    public function run_old(): void
    {
		$request = new Request();
		$router = new Router($this->moduleManager, $this->config, $request, $this->logger);		
		$response = $router->dispatch($_SERVER['REQUEST_URI'] ?? '/');

// Если контроллер вернул JSON — отправляем как JSON
        if (is_array($response) || is_object($response)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            return;
        }
		
        echo (string)$response;
    }
//-----------------------------------------------------
// 25.04.2026
    public function run(): void
    {
		$request = new Request();
		$router = new Router($this->moduleManager, $this->config, $request, $this->logger);

	try { 

		echo $router->dispatch($request->uri());

	} catch (\Throwable $e) {

    // можно логировать
		error_log($e->getMessage()); // смотри PHP-manual

// дублируется в /public/index.php
    $this->logger->error(
        $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()
    );

		echo $router->handleError(500, $e->getMessage() .' in ' . $e->getFile() . ':' . $e->getLine());
		}
    }
//------------------------------------------------------
    public function getEnv(): string
    {
        return $this->config['env'] ?? 'prod';
    }
//------------------------------------------------------
    public function getLogger(): Logger
    {
        return $this->logger;
    }
	
}

