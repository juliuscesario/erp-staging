<?php

namespace Config;

$routes = Services::routes();

$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();

$routes->get('/', 'Home::index');

// HMVC Auto-Router
$modulesPath = APPPATH . 'Modules/';
if (is_dir($modulesPath)) {
    $modules = scandir($modulesPath);
    foreach ($modules as $module) {
        if ($module === '.' || $module === '..') continue;
        $controllersPath = $modulesPath . $module . '/Controllers/';
        if (is_dir($controllersPath)) {
            $controllers = scandir($controllersPath);
            foreach ($controllers as $controller) {
                if ($controller === '.' || $controller === '..') continue;

                $controllerName = basename($controller, '.php');
                $route_path = strtolower($module) . '/' . strtolower($controllerName);
                $namespace = '\\Modules\\' . $module . '\\Controllers\\' . $controllerName;

                $routes->add($route_path, $namespace . '::index');
                $routes->add($route_path . '/(:any)', $namespace . '::$1');
            }
        }
    }
}

if (is_file(APPPATH . 'Config/Routes/Routes.php')) {
    require APPPATH . 'Config/Routes/Routes.php';
}