<?php
namespace Core;
/**
 * Базовый контроллер — helper'ы render() и json()
 */
use Core\Config;
use Core\Csrf;
use Core\Helper\Debug;
use Core\Auth\AdminAuthService;
use Core\Auth\UserAuthService;
use Core\Service\BreadcrumbService;

abstract class Controller
{
    protected ModuleManager $mm;	
	protected View $view;
	protected Logger $logger;	
	protected Request $request;
	protected Response $response;  
	protected AdminAuthService $authAdmin;    
	protected UserAuthService $authUser;

	protected Csrf $csrf;
	protected Flash $flash;
	protected string $module;
	protected string $controller;
	protected string $action;
	protected array $params;	
	protected string $lang;
    protected array $config;


	protected array $breadcrumb = [];
//-------------------------------
// базовая инициализация:
public function __construct()
{
#	echo '<p>'.__METHOD__.' : '.__LINE__.'</p>';

    $this->mm = new ModuleManager(); 
	$this->request = new Request();
	$this->response = new Response();
    $this->authAdmin = new AdminAuthService();
    $this->authUser = new UserAuthService();

    $this->logger = new Logger();
#	$this->config = new Config();	
	$this->csrf = new Csrf();
	$this->flash = new Flash();	
	
    $module = explode('\\', static::class)[0];
    $this->view = new View($module, $this->mm);
	
	$this->authUser->autoLogin();
}
//-------------------------------
public function debug(
		mixed $value, 
		string $title = '',		
		int $collapseAfter = 5,
		): void
{
    Debug::print(
			$value,  
			$title, 
			$collapseAfter
	);
}
//-------------------------------
    /**
     * Метод рендеринга шаблона.
     * Позволяет вызывать View из любого контроллера одной строкой:
     * return $this->render('user/profile', ['user' => $user]);
     */    
	 
protected function render(string $template, array $data = []): string
{
$backtrace = debug_backtrace(
			DEBUG_BACKTRACE_IGNORE_ARGS);
$backtrace = debug_backtrace();
#	$this->debug($backtrace);	
#	$this->debug($this->view);	
#	$this->debug($template);
#	$this->debug($data);	
	
//---------------------------------------------
// Shared variables
//---------------------------------------------
/*
	$this->debug([$this->module,
        $this->controller,
        $this->action,
        $this->params
		]);
*/
    $data['_csrf'] = $this->csrf;
    $data['_config'] = $this->config;
	$data['_flash'] = $this->flash;    
	$data['_module'] = $this->module;
    $data['_controller'] = $this->controller;
    $data['_action'] = $this->action;
    $data['_params'] = $this->params;	
	
    $data['_lang'] = $_SESSION['lang'];

    $data['_authAdmin'] =  $this->authAdmin;
    $data['_authUser'] =  $this->authUser;
	
#	$data['_breadcrumb'] = $this->getBreadcrumb();


#	$this->debug($data);

/*
Сейчас передаётся 4 аргумента:

setContext(
    string $_module,
    string $_controller,
    string $_action,
    array $_params
)
Через полгода может стать:

$_module
$_controller
$_action
$_params
$_user
$_request
$_layout

и сигнатура разрастётся.
*/
	
	$this->view->setContext(
	    [
        'module'    => $this->module,
        'controller'=> $this->controller,
        'action'    => $this->action,
        'params'    => $this->params,
		'data'		=> $data,
		
		]
	);
//-----------
// Render
//-----------

    return $this->view->render($template, $data);
}
//-----------
	 protected function render_old(string $template, array $data = [], $layout = null): string
    {
        $view = new View($template, $data, $layout);
        return $view->render();
    }
//---------------------------------------------
    /**
     * Метод для быстрого JSON-ответа, если используется AJAX.
     * Пример: return $this->json(['status' => 'ok']);
     */
    protected function json(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
//        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);		
        exit;
    }
//------------------------------------------
// 12.04.2026: (Чтобы менять в каждом контроллере при необходимости)	
    protected function setLayout(string $layout): void
    {
        $this->view->setLayout($layout);
    }
/**
 * -----------------------------------------
 * Проверка CSRF
 * -----------------------------------------
 */
	protected function validateCsrf(): void
	{
		$token = $_POST['_csrf'] ?? null;

		if (
#			!$this->csrf->validateToken($token)
			!$this->csrf->getToken($token)			
		) {

			die('Invalid CSRF token.');
		}
	}
	
/**
 * -----------------------------------------
 * Route context 15.05.2026
 * -----------------------------------------
 */
public function setRouteContext(
    string $module,
    string $controller,
    string $action,
    array $params,
	array $config,
	string $lang
	
): void {

    $this->module = $module;
    $this->controller = $controller;
    $this->action = $action;
    $this->params = $params;
    $this->config = $config;	
    $this->lang = $lang;
}	
/**
 * ------------------------------
 * Redirect helper
 * ------------------------------
 */	
//-------------------------------
protected function redirect(string $url): void
{
    $this->response->redirect($url);
/*
		вместо:	
        header("Location: $url");
        exit;
*/		
}
//--------------------------------
public function setBreadcrumb(
    array $breadcrumb
): void {

    $this->breadcrumb = $breadcrumb;
}
//--------------------------------
protected function getBreadcrumb(): array
{
/*	
	$this->debug([
		$this->module,
        $this->controller,
        $this->action,
        $this->params
		]);
*/	
    return BreadcrumbService::build(

        $this->module,
        $this->controller,
        $this->action,
        $this->params ?? []
    );
}
//--------------------------------
   protected function printMyMethodsOnly($class) {
        // Получаем имена только тех методов, которые объявлены в этом классе
	
	if (!is_object($class)) {
		
		$class = new $class([]);
		
	}
		$className = get_class($class); 
#		echo $className; 		
/*		
        $methods = array_map(
            fn($m) => $m->getName(), 
            array_filter(
                (new \ReflectionClass($class))->getMethods(), 
                fn($m) => $m->getDeclaringClass()->getName() === get_class($class)
            )
        );
*/	
	$methods = $this->getMyMethodsOnly($class);

	$this->debug($methods,$className);
 #       print_r($methods);
}
//--------------------------------
   protected function getMyMethodsOnly($class) {
        // Получаем имена только тех методов, которые объявлены в этом классе

	if (!is_object($class)) {
		$class = new $class([]);
	}
       return array_map(
            fn($m) => $m->getName(), 
            array_filter(
                (new \ReflectionClass($class))->getMethods(), 
                fn($m) => $m->getDeclaringClass()->getName() === get_class($class)
            )
        );
    }
//--------------------------------
   protected function printDiffMethods($class1, $class2) {

	$array1 = $this->getMyMethodsOnly($class1);
	$array2 = $this->getMyMethodsOnly($class2);

	$result1 = array_diff($array1, $array2);
	$result2 = array_diff($array2, $array1);

	?>

	<table><tr style="vertical-align: top;"><td>
<?php 
	$this->debug($result1);
	$this->debug($result2);
	
	
?>
	</td><td>
<?php
	$this->printMyMethodsOnly($class1);
?>
	</td><td>
<?php
	$this->printMyMethodsOnly($class2);
?>
	</td></tr></table>
<?php
   }
//--------------------------------


//--------------------------------


//--------------------------------
 





	

}
