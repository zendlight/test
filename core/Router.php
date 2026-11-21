<?php
namespace Core;

use Core\Helper\Str;
use Core\Helper\Debug;

// Router с ленивой загрузкой модулей
class Router
{
    private array $routes;
    private ModuleManager $moduleManager;
	protected array $config;
	protected Request $request;
	protected bool $handlingError = false;
	protected Logger $logger;
	protected Application $app;	
    private string $env;	
	protected RouterNormalizer $normalizer;	
	protected string $action;	// 15.05.2026
	protected array $params;	// 20.06.2026	
	protected array $routeParams = [];	

#-----------------------------------------------------
// вызывается внутри функции /core/Application->run(); 

public function __construct(ModuleManager $mm, array $config, Request $request, Logger $logger)
{
    $this->moduleManager = $mm;
    $this->config = $config;
    $this->request = $request;
    $this->logger = $logger;
	$this->app = new Application($config);
	$this->env = $this->app->getEnv();
	$this->normalizer = new RouterNormalizer(); //12.05.2026
	
#    $this->debug($config);
}
#-----------------------------------------------------
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
#-----------------------------------------------------
// вызывается внутри функции /core/Application->run();

	public function dispatch(string $uri)
	{
#	echo '$this->env = ' . $this->env . '<br>';		

	$url = $_SERVER['REQUEST_URI'];
#	echo '$url = ' . $url . '<br>';	
	$url = parse_url($url, PHP_URL_PATH);
	$url = urldecode($url);
#	echo '$url = ' . $url . '<br>';

	$this->logger->info("URI: $uri");	
	$this->logger->access($_SERVER['REQUEST_METHOD'] . ' ' . $uri);
	
//---------------------------
// Шаг 0 — перевести URL в нижний регистр	

	$path = $this->normalizer->normalizePath($uri);

	$segments = $path === '' ? [] : explode('/', $path);

	$this->logger->debug('Segments: ' . json_encode($segments));

//---------------------------
// Шаг 1 — определить язык и модуль из URL
	
//	$segments = explode('/', trim($path, '/'));
#	$segments = array_map('strtolower', $segments); // не продумано до конца!!! 2026-05-06

// 👉 язык извлекается отдельно
	$langResolver = new LanguageResolver($this->config);
	$result = $langResolver->resolve($segments);
	$lang = $result['lang'];
	$segments = $result['segments'];
	$_SESSION['lang'] = $lang;		
	Translator::init($lang);
#	echo '$lang = ' . $lang . '<br>';
	
// 👉 дальше Router работает как будто языка НЕТ
	$routePath = '/' . implode('/', $segments);


#	$this->debug($routePath);
#    $this->debug($segments);
	
	if(empty($segments[0])) return $this->runController('Application', 'Index', 'index', []);

	$offset = 0;
	
	// модуль
	$modules = $this->config['modules'] ?? [];		

#    $this->debug($modules);

	$current = strtolower($segments[$offset]);
	
#	$this->debug($current);

	foreach ($modules as $moduleName) {

#		echo $this->normalizeModuleName($moduleName).'<br>';

		if ($current === $this->normalizeModuleName($moduleName)) {

			$module = $moduleName;
			$offset++;

		break;
		}
	}
	if(empty($module))  $module = 'Application';	

#	$this->debug($module);

// 12.05.2026: это избыточная проверка, потом можно удалить: 	
	// Fallback логика маршрутизации	
	if (!in_array($module, $this->config['modules'])) {

		// 👉 неизвестный модуль → fallback
		return $this->handleError(404, "Module not found");
	}
	
//---------------------------	
// Шаг 2 — загрузить модуль
/*
	Здесь исполнять модуль слишком рано. Это нужно делать, когда будут определеныController или action. Если Controller или action не найден, то вызывается ErrorModule.
*/

	$this->moduleManager->load($module);

//---------------------------	
// Шаг 3 — взять config модуля

	$moduleConfig = $this->moduleManager->getConfig($module);

#	$this->debug($routePath);

    $routes = $moduleConfig['routes'] ?? [];

#    $this->debug($routes);

	$routeMatch = $this->matchRoute(
		$routePath,
		$routes
	);

#    $this->debug($routeMatch);

	if ($routeMatch) {

//---------------------------		
// Шаг 4 — искать route ТОЛЬКО внутри модуля (путь без модуля и языка)

	$route  = $routeMatch['route'];
	$params = $routeMatch['params'];

#    $this->debug($route);
#    $this->debug($params);

	$controller = $route['controller'];

	$action = $params['action'];	
	$action = Str::camel($action);
	$action .= 'Action';

    $this->logger->info("Dispatching: $module\\$controller@$action");
    $this->logger->debug(
        "Route => module=$module, controller=$controller, action=$action, params=" . json_encode($params)
    );

    return $this->runController(
        $module,
        $controller,
        $action,
        $params
    );

} else {
	
#	echo '<br>'.__METHOD__.' : '.__LINE__.'<br>';	

    // 👉 ПРОБУЕМ fallback (slug)
    $this->logger->debug("No route match, trying autoRoute...");

    $result = $this->autoRoute($segments, $offset);

    if ($result) {
        return $result;
    }

    // 👉 если и fallback не помог → 404
    $this->logger->error("Route not found: $uri");

    return $this->handleError(404, "Page not found");
}
//---------------------------		
// Шаг 5 — fallback (автоматический роутинг - если ничего не найдено)

#	echo '<br>'.__METHOD__.' : '.__LINE__.'<br>';
		
// 👉 если авто-роутинг включён
	if (!empty($this->config['router']['auto_route'])) {
		return $this->autoRoute($segments, $offset);
	}

#	echo '<br>'.__METHOD__.' : '.__LINE__.'<br>';	

// 👉 иначе — ошибка
	return $this->handleError(404, 'Route not found');		
}
#----------------------------------------
	
protected function runController(
		string $module, 
		string $controller, 
		string $action, 
		array $params = []
) {
	$lang = $_SESSION['lang'];
#	echo '<br>'.__METHOD__.' : '.__LINE__.'<br>';	
#	echo '$lang = ' . $lang . '<br>';
	echo '$module = ' . $module . '<br>';	
	echo '$controller = ' . $controller . '<br>';
	echo '$action = ' . $action . '<br>';
	
#   $this->debug($params);
#   $this->debug($_COOKIE);


	$this->action = $action;	
	$this->params = $params;
	
    // 👉 ВАЖНО: записываем маршрут в Request
    $this->request->setRoute([
		'lang' 			=> $lang, 	
        'module'     	=> $module,
        'controller' 	=> $controller,
        'action'     	=> $action,
        'params'     	=> $params,
    ]);
//---------	
// 27.04.2026:

    $this->logger->debug(
        "Route => module=$module, controller=$controller, action=$action, params=" . json_encode($params)
    );
	$this->logger->info("Dispatching: $module\\$controller@$action");	
// возможно это дубляж:
	$uri = $_SERVER['REQUEST_URI'];
	$this->logger->info("URI: $uri");
	$this->logger->debug("Module: $module, Controller: $controller, Action: $action");
//---------	
#	echo '<br>'.__METHOD__.' : '.__LINE__.'<br>';
    try {
        $this->moduleManager->execute($module);

		$controllerClass = "\\$module\\Controller\\{$controller}Controller";
		
#	echo '<br>'.__METHOD__.' : '.__LINE__.'<br>';			
//------------>>
#	echo '<b>$controllerClass = ' . $controllerClass . '</b><br>';		

        // ❌ нет контроллера
        if (!class_exists($controllerClass)) {
		
#	echo '<br>'.__METHOD__.' : '.__LINE__.'<br>';				
	
		$this->logger->error("Controller not found: $controllerClass");
	
#	echo '$this->env = ' . $this->env . '<br>';		
	
		if ($this->app->getEnv() === 'dev') {
			throw new \Exception("Controller $controllerClass not found");
		}
	
// 👉 если это уже ErrorModule — не зацикливаемся
		if ($module === 'Error') {
			http_response_code(500);
			return "<h1>500</h1><p>ErrorController not found</p>";
		}
			
#	echo '<br>'.__METHOD__.' : '.__LINE__.'<br>';	

		return $this->handleError(500, "Controller $controllerClass not found");				
        }
//------------<<
		// 👉 ВАЖНО: передаём ModuleManager
		
		
		
		$instance = new $controllerClass(
			$params,	
			$this->moduleManager,
			$this->request,

		);

        // ❌ нет метода
        if (!method_exists($instance, $action)) {
			
#	echo '<br>'.__METHOD__.' : '.__LINE__.'<br>';			

//			$this->logger->error("404 Not Found: $uri");
			
			$this->logger->error("404 Action $action not found in $controllerClass");
			
            return $this->handleError(404, "Action $action not found in $controllerClass");
        }

#    $this->debug($params);

		$instance->setRouteContext(
			$module,
			$controller,
			$action,
			$params,
			$this->config,
			$lang		
		);

        return $instance->$action($params);

    } catch (\Throwable $e) {

#    $this->debug($e);

        // ❌ ошибка внутри action
		
		$this->logger->error(
			"500 Exception: " . $e->getMessage() . 
			" in " . $e->getFile() . ", Line: " . $e->getLine()
		);	
		
#		echo '<b>getMessage = </b>' . $e->getMessage() .'<br>';
#		echo '<b>getCode = </b> ' . $e->getCode() .'<br>';
#		echo '<b>getFile = </b>' . $e->getFile() .'<br>';
#		echo '<b>getLine = </b>' . $e->getLine() .'<br>';

#	$this->debug($e->getTrace());

#		echo '<b>getTraceAsString = </b>' . $e->getTraceAsString() .'<br>';
#		echo '<b>getPrevious = </b>' . $e->getPrevious() .'<br>';
#		echo '<b>__toString = </b>' . $e->__toString() .'<br>';
	
/*
Throwable::getMessage — Gets the message
Throwable::getCode — Gets the exception code
Throwable::getFile — Gets the file in which the object was created
Throwable::getLine — Gets the line on which the object was instantiated
Throwable::getTrace — Gets the stack trace
Throwable::getTraceAsString — Gets the stack trace as a string
Throwable::getPrevious — Returns the previous Throwable
Throwable::__toString — Gets a string representation of the thrown object
*/	
    return $this->handleError(500, $e->getMessage() . ' <br><b>in</b> ' . $e->getFile() .'<b>, Line: </b>' . $e->getLine());
}
}
#----------------------------------------
// 29.04.2026:
protected function autoRoute(array $segments, $offset)
{
#	echo '<br>'.__METHOD__.' : '.__LINE__.'<br>';	

    if (empty($segments[$offset])) {
        return null;
    }

#	echo '<br>'.__METHOD__.' : '.__LINE__.'<br>';	


    $slug = $segments[$offset];
/*
    // 👉 пример: проверка существования slug
    if (!$this->slugExists($slug)) {
        return null;
    }
*/
    $this->logger->info("AutoRoute slug: $slug");

    return $this->runController(
        'Application',
        'About',
        'slug',
        ['slug' => $slug]
    );
}
/*	26.04.2026:
	6. Дополнительная защита (очень полезно)

В autoRoute():
	if (!class_exists($controllerClass) || !method_exists($instance, $action)) {
    return $this->handleError(404);
}	
*/
#-------------------------------------------------
public function handleError(int $code, string $message = '')

{
#	echo '<br>'.__METHOD__.' : '.__LINE__.'<br>';		
	
    // 👉 если уже внутри ошибки — аварийный вывод
    if ($this->handlingError) {

        http_response_code($code);
		
		$this->logger->error("HTTP $code: $message");

        return "<h1>$code</h1><p>$message</p>";
    }

    $this->handlingError = true;

    $action = match ($code) {
        404 => 'notFound',
        403 => 'accessDenied',
        default => 'serverError',
    };

    return $this->runController(
        'Error',
        'Error',
        $action,
        ['message' => $message]
    );
}
#-----------------------------------------------------
// 09.06.2026 from ChatGPT

protected function matchRoute(
    string $path,
    array $routes
): ?array {

    // Оригинальный URL
    $originalPath = trim($path, '/');

    // Нормализованный URL для сравнения
    $normalizedPath = mb_strtolower(
        $originalPath,
        'UTF-8'
    );

    foreach ($routes as $route) {

        switch ($route['type']) {

//====================
            // LITERAL
//====================

            case 'literal':

                $routePath = mb_strtolower(
                    trim($route['path'], '/'),
                    'UTF-8'
                );

                if ($normalizedPath === $routePath) {

                    return [

                        'route' => $route,

                        'params' => [

                            'action' =>
                                $route['action'] ?? 'index',
                        ],
                    ];
                }

                break;

//====================
            // SEGMENT
//====================

            case 'segment':

                $params = $this->matchSegment(
                    $route,
                    $originalPath
                );

                if ($params !== null) {

                    // Если action не пришёл из URL,
                    // берём из маршрута

                    if (
                        !isset($params['action'])
                        && isset($route['action'])
                    ) {
                        $params['action']
                            = $route['action'];
                    }

                    return [

                        'route'  => $route,

                        'params' => $params,
                    ];
                }

                break;

//====================
            // REGEX
//====================

            case 'regex':

                if (
                    preg_match(
                        $route['pattern'],
                        $originalPath,
                        $matches
                    )
                ) {

                    $params = array_filter(
                        $matches,
                        'is_string',
                        ARRAY_FILTER_USE_KEY
                    );

                    if (
                        !isset($params['action'])
                        && isset($route['action'])
                    ) {
                        $params['action']
                            = $route['action'];
                    }

                    return [

                        'route'  => $route,

                        'params' => $params,
                    ];
                }

                break;

//====================
            // WILDCARD
//====================

            case 'wildcard':

                return [

                    'route' => $route,

                    'params' => [

                        'action' =>
                            $route['action']
                            ?? 'index',
                    ],
                ];
        }
    }
    return null;
}
#-------------------------------------------------
/* ChatGPT:
Если ты регистрируешь отдельные маршруты:
/moderator-backend/comment
/moderator-backend/comment/{id}
/moderator-backend/comment/{action}/{id}
то matchSegment() должна быть максимально простой и строгой.
Вот полный вариант, который я бы использовал в ZendLight:


Если я регистрирую отдельные маршруты:
/article/comment
/article/comment/{id}
/article/comment/{id}/{slug}
/article/comment/{action}/{id}
/article/comment/{action}/{id}/{slug}
где slug может включать буквы любого алфавита:
$slag = "текст для поисковика может использовать любой алфавит".

как в таком случае изменится функция matchSegment() ?
*/
protected function matchSegment(
    array $route,
    string $path
): ?array {

    $pattern = $route['path'];

    $constraints = $route['constraints'] ?? [];

    // Оригинальные сегменты URL
    $realPathParts = explode(
        '/',
        trim($path, '/')
    );

    // Для сравнения приводим к lowercase
    $normalizedPathParts = array_map(
        static fn($v) => mb_strtolower($v),
        $realPathParts
    );

    // Шаблон маршрута
    $patternParts = explode(
        '/',
        mb_strtolower(trim($pattern, '/'))
    );

    // Количество сегментов должно совпадать
    if (count($normalizedPathParts) !== count($patternParts)) {
        return null;
    }

    $params = [];

    foreach ($patternParts as $i => $part) {

        // -------------------------
        // Параметр {name}
        // -------------------------

        if (
            preg_match(
                '/^\{([a-zA-Z_][a-zA-Z0-9_]*)\}$/',
                $part,
                $matches
            )
        ) {

            $key = $matches[1];

            $value = $realPathParts[$i];

            // Проверка constraint
            if (!empty($constraints[$key])) {

                if (
                    !preg_match(
                        '#^' . $constraints[$key] . '$#u',
                        $value
                    )
                ) {
                    return null;
                }
            }

            $params[$key] = $value;

            continue;
        }

        // -------------------------
        // Статический сегмент
        // -------------------------

        if ($part !== $normalizedPathParts[$i]) {
            return null;
        }
    }

    return $params;
}


#-------------------------------------------------
//12.05.2026:
protected function normalizeModuleName(string $name): string
{
    return strtolower(
        preg_replace('/(?<!^)[A-Z]/', '-$0', $name)
    );
}	




}


