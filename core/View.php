<?php
namespace Core;

use Core\Translator;
use Core\Helper\Debug;
use Core\Helper\File;
use Core\Service\BreadcrumbLightService;

class View
{
    protected string $module;
    protected string $layout = '';
    protected string $layoutPath;	
    protected ModuleManager $moduleManager;
    protected ModuleManager $mm;
	protected Logger $logger;	
    protected Config $config;
    protected array $context;
#   protected array $params;	

//----------------------------------------
public function __construct(string $module, ModuleManager $mm)
{
    $this->module = $module;
    $this->mm = $mm;
    $this->logger = new Logger();	
}
//----------------------------------------
public function render(string $template, array $data = []): string
{
    $content = $this->renderTemplate($template, $data);

	return $this->renderLayout($content, $data);
}
//----------------------------------------
protected function renderTemplate_old(
	string $template, 
	array $data
): string
{
    extract($data);

    $templatePath = ROOT . 
			"/module/{$this->module}/view/$template.phtml";
	
	echo '$templatePath = '.$templatePath.'</br>';	

//	проверяем реальное наличие файла:	
    if (!file_exists($templatePath)) {
		$this->logger->error(
			'Template не найден: ' . $templatePath);
        throw new \Exception(
			"Шаблон не найден: $templatePath");		
        return "Template not found: $templatePath";
    }	
	
    ob_start();
    include $templatePath;
    return ob_get_clean();
}
//----------------------------------------
protected function renderTemplate(
    string $template,
    array $data
): string
{
    extract($data);

    $templatePath =
        $this->resolveTemplatePath($template);

	echo '$templatePath = '.$templatePath.'</br>';	

    ob_start();
    include $templatePath;
    return ob_get_clean();
}
//----------------------------------------
protected function resolveTemplatePath(
    string $template
): string
{
    $templatePath =
        ROOT .
        "/module/{$this->module}/view/$template.phtml";

    if (!file_exists($templatePath)) {

        $this->logger->error(
            'Template not found: ' . $templatePath
        );

        throw new \Exception(
            "Template not found: {$templatePath}"
        );
    }

    return $templatePath;
}
//-------------------------------------------------
public function breadcrumbLight(): string
{		
#	$this->debug($this->context);


    return $this->component(
        $this->context['module'],
        'breadcrumb-light',
        $this->context,
    );	
}
//-------------------------------------------------
public function component(
    string $module,
    string $component,
    array $data = []
): string {

    extract($data);

    $file = ROOT . '/module/' . $module .
        '/components/' . $component . '.phtml';

    if (!file_exists($file)) {
        return '';
    }

    ob_start();

    include $file;

    return ob_get_clean();
}
//----------------------------------------
protected function renderLayout(
		string $content, 
		array $data
): string
{
    $layoutPath = $this->detectLayout();	
		
    extract($data);

// если layout отключён
    if (!$layoutPath) {
        return $content;
    }
// layout
    if (!file_exists($layoutPath)) {
		$this->logger->error('layout не найден: ' . $layoutPath);
		throw new \Exception("layout не найден: {$layoutPath}");
	}

    ob_start();
    include $layoutPath;
    return ob_get_clean();
}
//----------------------------------------
protected function detectLayout()
{
/*
	Места, где может лежать layout:
		1. AJAX — без layout
		2. controller->setLayout();
		3. configModule
		4. Application::layout
		5. config['base_layout']
*/
    if ($this->isAjax()) {
        $this->layout = null;
        return;
    }

// 👉 проверяем, был ли layout определен  в controller	
#	echo '$this->layout = ' . $this->layout .'</br>';		
	if($this->layout) {
		$layoutPath = $this->resolveLayoutPath($this->layout); 
		if($layoutPath) return $layoutPath;
	}

// 👉 пробуем взять layout из config модуля
#	echo '<br>'.__METHOD__.' : '.__LINE__.'<br>';
#	echo '$this->module = ' . $this->module .'</br>';
    $this->mm->load($this->module);
	$configModule = $this->mm->getConfig($this->module);
		
    // 👉 если layout определен в configModule: 
    if(!empty($configModule['layout'])) {
		
		$layout = $configModule['layout'];		
#		echo '$layout = ' . $layout .'</br>';

		$layoutPath = $this->resolveLayoutPath($layout); 
		if($layoutPath) return $layoutPath;
	}		
/*	
	👉 Если layout модуля не найден в текущем модуле, 
	берем layout из глобального config:
*/	
	$this->config = new Config();
	$layout = $this->config->get('base_layout');
		
#		echo '$layout = ' . $layout .'</br>';
		
	if($layout) {
		$layoutPath = ROOT . $layout; 
		if(file_exists($layoutPath)) return $layoutPath;
		else {
		$this->logger->error('layout не найден: ' . $layoutPath);
		throw new \Exception("Layout не найден: {$layoutPath}");
		return null;
	}}

// 👉 если layout вообще нигде не найден, то это ещё не ошибка:
	return null;
}
//----------------------------------------
protected function resolveLayoutPath(string $layout) 
{
/*
	👉 если layout лежит в другом модуле, например: 
	'layout' => 'Application::layout/backend/main',
	'layout' => 'Application::layout/responsive/main',
*/
    if (str_contains($layout, '::')) {

        [$module, $tpl] = explode('::', $layout);
        $layoutPath = ROOT . "/module/$module/view/$tpl.phtml";				
	} else {
/*
	👉 если layout лежит в текущем модуле, например:
	'layout' => 'layout/responsive/main',
*/
		$layoutPath = ROOT . "/module/$this->module/view/$layout.phtml";
	}
#	echo '$layoutPath = ' . $layoutPath .'</br>';		
		
	if(file_exists($layoutPath)) return $layoutPath;
	else {
		$this->logger->error('layout не найден: ' . $layoutPath);
		throw new \Exception("Layout не найден: {$layoutPath}");
		return null;
	}
}
//-------------------------------------
    // ручное управление (если нужно)
	// Пример: $this->setLayout('layout/ajax');
	
    public function setLayout(string $layout): void
    {
        $this->layout = $layout;
    }
//-------------------------------------
    public function getLayout()
    {
        return $this->layout ?? null;
    }
//-------------------------------------
    public function disableLayout(): void
    {
        $this->layout = '';
    }	
//-------------------------------------
public function setContext(array $data): void
{
	$this->context 	= $data;	
}
//-------------------------------------
public function getContext()
{
    return $this->context ?? [];
}	
//--------------------------------------
    protected function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
	/*
	Пример использования:
	JavaScript:
		fetch('/en/user/list', {
		headers: { 'X-Requested-With': 'XMLHttpRequest' }
		})	
	вернётся чистый HTML без layout
	*/
//-------------------------------------
/**
 * ----------------
 * Translate text
 * ----------------
 */
public function t(string $key, array $params = []): string 
{
    return Translator::t($key, $params);
}
//------------------
public function __(string $key, array $params = []): string 
{
    return $this->t($key, $params);
}
//-------------------------------------
/**
 * Escape HTML
 */
public function escape(
    string $value = null
): string {

    return htmlspecialchars(
        $value,
        ENT_QUOTES,
        'UTF-8'
    );
}
//-------------------------------------
/**
 * Alias escape()
 */
public function escapeHtml(
    string $value
): string {

    return $this->escape($value);
}
//-------------------------------------
/**
 * Short alias
 *
 * <?= $this->e($value) ?>
 */
public function e(
    string $value
): string {

    return $this->escape($value);
}
//-------------------------------------
/**
 * Escape URL
 */
public function escapeUrl(
    string $value
): string {

    return rawurlencode($value);
}
//-------------------------------------
/**
 * Escape attribute
 */
public function escapeAttr(
    string $value
): string {

    return htmlspecialchars(
        $value,
        ENT_QUOTES,
        'UTF-8'
    );
}
//-------------------------------------
public function debug(
	mixed $value, 
	string $title = '',
	int $collapseAfter = 5
): void
{
    Debug::print($value, $title, $collapseAfter);
}
//-------------------------------------
public function showEntity(
array $entity,
array $options1 = [],
): void
{
	$labels = $this->getLabels('entity-labels');
	
	$options2 = [
		'title'      => 'Entity',
		'selected'   => ['id',],
		'hideEmpty'  => true,
		'debug'      => false,
	];
	$options = array_replace($options2, $options1);
    extract($options);
	if($debug) 	$this->debug($entity);
	if($debug)	$this->debug($options);
	if($debug) 	$this->debug($selected);
	if($debug) 	$this->debug($labels);	

	$file = ROOT . '/shared/show-entity.phtml';
	if (!is_file($file)) {
		throw new RuntimeException("Template not found: {$file}");
	}
	include $file;
}
//-------------------------------------
public function showList(
array $entities, 
array $options1 = [],
): void
{
	$labels = $this->getLabels('entity-labels');
	
	$options2 = [
		'title'     => 'Entity List',
		'selected'  => ['id',],
		'link'		=>	null,
		'debug'     => false,
	];
	$options = array_replace($options2, $options1);
    extract($options);	
	if($debug) 	$this->debug($entities);
	if($debug)	$this->debug($options);
	if($debug) 	$this->debug($selected);
	if($debug) 	$this->debug($labels);

	$file = ROOT . '/shared/show-list.phtml';
	if (!is_file($file)) {
		throw new RuntimeException("Template not found: {$file}");
	}	
	include $file;
}
//-------------------------------------
public function getLabels(string $file = 'entity-labels'): array
{
	$labels = require ROOT . "/shared/$file.php";
	return $labels;
}
//-------------------------------------
public function formatValue(mixed $value): string
{
    if ($value === null) {
        return '';
    }

    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }

    if (is_array($value)) {
        return '<pre>' .
            htmlspecialchars(print_r($value, true)) .
            '</pre>';
    }
	
    if ($value instanceof DateTimeInterface) {
        return $value->format('Y-m-d H:i:s');
    }
	
    if(strlen($value) > 60) {	
        return '<pre>'.htmlspecialchars($value).'</pre>';
    }
	
    return htmlspecialchars((string)$value);
}
//----------------------------------------
public function show(
    string $template,
    array $data = []
): void
{
    if (!empty($data)) {
        extract($data, EXTR_SKIP);
    }

    include $this->resolveTemplatePath($template);
}
//----------------------------------------
public function showShared(
    string $template,
    array $data = []
): void
{
    extract($data, EXTR_SKIP);

    $file = ROOT
        . '/shared/'
        . $template
        . '.phtml';

    if (!file_exists($file)) {
        throw new \RuntimeException(
            "Shared template not found: {$file}"
        );
    }

    include $file;
}
}

/*


$this->showEntity($user);
$this->showList($users);
$this->showForm($form);
$this->showTabs($tabs);
$this->showAlert('Saved');
$this->showCard(...);
$this->showBadge(...);
$this->showPagination(...);
$this->showToolbar(...);
$this->showBreadcrumb(...);
$this->showTable(...);
$this->showTimeline(...);
$this->showJson(...);
$this->showDump(...);
*/