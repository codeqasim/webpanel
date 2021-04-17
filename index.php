<?php
namespace AppRouter;use Exception;use DateTime;use DateInterval;use InvalidArgumentException;class Router{const NO_ROUTE_FOUND_MSG='No route found';private $routes;private $error;private $baseNamespace;private $currentPrefix;private $service=null;public function __construct($error,$baseNamespace=''){$this->routes=[];$this->error=$error;$this->baseNamespace=$baseNamespace==''?'':$baseNamespace.'\\';$this->currentPrefix='';}public function setService($service){$this->service=$service;}public function getService($service){return $this->service;}public function route($method,$regex,$handler){if($method=='*'){$method=['GET','PUT','DELETE','OPTIONS','TRACE','POST','HEAD'];}foreach((array)$method as $m){$this->addRoute($m,$regex,$handler);}return $this;}private function addRoute($method,$regex,$handler){$this->routes[strtoupper($method)][$this->currentPrefix.$regex]=[$handler,$this->service];}public function mount($prefix,callable $routes,$service=false){$previousPrefix=$this->currentPrefix;$this->currentPrefix=$previousPrefix.$prefix;if($service!==false){$previousService=$this->service;$this->service=$service;}$routes($this);$this->currentPrefix=$previousPrefix;if($service!==false){$this->service=$previousService;}return $this;}public function get($regex,$handler){$this->addRoute('GET',$regex,$handler);return $this;}public function post($regex,$handler){$this->addRoute('POST',$regex,$handler);return $this;}public function put($regex,$handler){$this->addRoute('PUT',$regex,$handler);return $this;}public function head($regex,$handler){$this->addRoute('HEAD',$regex,$handler);return $this;}public function delete($regex,$handler){$this->addRoute('DELETE',$regex,$handler);return $this;}public function options($regex,$handler){$this->addRoute('OPTIONS',$regex,$handler);return $this;}public function trace($regex,$handler){$this->addRoute('TRACE',$regex,$handler);return $this;}public function connect($regex,$handler){$this->addRoute('CONNECT',$regex,$handler);return $this;}public function dispatch($method,$path){if(!isset($this->routes[$method])){$params=[$method,$path,404,new HttpRequestException(self::NO_ROUTE_FOUND_MSG)];return $this->call($this->error,$this->service==null?$params:array_merge([$this->service],$params));}else{foreach($this->routes[$method]as $regex=>$route){$len=strlen($regex);if($len>0){$callback=$route[0];$service=isset($route[1])?$route[1]:null;if($regex[0]!='/')$regex='/'.$regex;if($len>1&&$regex[$len-1]=='/')$regex=substr($regex,0,-1);$regex=str_replace('@','\\@',$regex);if(preg_match('@^'.$regex.'$@',$path,$params)){array_shift($params);try{return $this->call($callback,$service==null?$params:array_merge([$service],$params));}catch(HttpRequestException $ex){$params=[$method,$path,$ex->getCode(),$ex];return $this->call($this->error,$this->service==null?$params:array_merge([$this->service],$params));}catch(Exception $ex){$params=[$method,$path,500,$ex];return $this->call($this->error,$this->service==null?$params:array_merge([$this->service],$params));}}}}}return $this->call($this->error,array_merge($this->service==null?[]:[$this->service],[$method,$path,404,new HttpRequestException(self::NO_ROUTE_FOUND_MSG)]));}private function call($callable,array $params=[]){if(is_string($callable)){if(strlen($callable)>0){if($callable[0]=='@'){$callable=$this->baseNamespace.substr($callable,1);}}else{throw new InvalidArgumentException('Route/error callable as string must not be empty.');}$callable=str_replace('.','\\',$callable);}if(is_array($callable)){if(count($callable)!==2)throw new InvalidArgumentException('Route/error callable as array must contain and contain only two strings.');if(strlen($callable[0])>0){if($callable[0][0]=='@'){$callable[0]=$this->baseNamespace.substr($callable[0],1);}}else{throw new InvalidArgumentException('Route/error callable as array must contain and contain only two strings.');}$callable[0]=str_replace('.','\\',$callable[0]);}return call_user_func_array($callable,$params);}public function dispatchGlobal(){$pos=strpos($_SERVER['REQUEST_URI'],'?');return $this->dispatch($_SERVER['REQUEST_METHOD'],'/'.trim(substr($pos!==false?substr($_SERVER['REQUEST_URI'],0,$pos):$_SERVER['REQUEST_URI'],strlen(implode('/',array_slice(explode('/',$_SERVER['SCRIPT_NAME']),0,-1)).'/')),'/'));}}class HttpRequestException extends Exception{}

session_start();
// create htaccess file for routing
if(!file_exists('.htaccess'))
{
$content = 'RewriteEngine On' . "\n";
$content .= 'RewriteCond %{REQUEST_FILENAME} !-d' . "\n";
$content .= 'RewriteCond %{REQUEST_FILENAME} !-f' . "\n";
$content .= 'RewriteCond %{REQUEST_FILENAME} !-l' . "\n\n";
$content .= 'RewriteRule ^(.+)$ index.php?url=$1 [QSA,L]';
file_put_contents('.htaccess', $content);
}

$root=(isset($_SERVER['HTTPS']) ? "https://" : "http://").$_SERVER['HTTP_HOST'];
$root.= str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
define('root', $root);
use AppRouter\Router;

/* 404 page init */
$router = new Router(function ($method, $path, $statusCode, $exception) {
http_response_code($statusCode);
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
echo "Error 404 - Page not found";
}); ?>

<?php $router->post('login', function() {
if ($_POST['username'] == "admin" && $_POST['password'] == "admin" ) {
$_SESSION['webpanel_user_log'] = true;
// echo $_SESSION['webpanel_user_log'];
header("Location: ".root);
} else header("Location: ".root);
}) ?>

<?php $router->get('logout', function() { session_destroy(); header("Location: ".root); }); ?>
<?php $router->get('login', function() { ?>

<!DOCTYPE HTML>
<title>Login</title>
<link rel="stylesheet" href="<?=root?>style.css" />

<div style="width: 400px; margin: 100px auto; text-align: center;">
<img id="imgPoweredByCpanel" src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNTE2IiBoZWlnaHQ9IjMyMCIgdmlld0JveD0iMCAwIDExMzcgMjQwIj48ZGVmcz48Y2xpcFBhdGggaWQ9ImEiPjxwYXRoIGQ9Ik0xMDk1IDBoNDEuNTc4djQySDEwOTV6bTAgMCIvPjwvY2xpcFBhdGg+PC9kZWZzPjxwYXRoIGQ9Ik04OS42OSA1OS4xMDJoNjcuODAybC0xMC41IDQwLjJjLTEuNjA1IDUuNi00LjYwNSAxMC4xLTkgMTMuNS00LjQwMiAzLjQtOS41MDQgNS4wOTYtMTUuMyA1LjA5NmgtMzEuNWMtNy4yIDAtMTMuNTUgMi4xMDItMTkuMDUgNi4zLTUuNTA1IDQuMi05LjM1MyA5LjkwNC0xMS41NTIgMTcuMTAzLTEuNCA1LjQtMS41NSAxMC41LS40NSAxNS4zMDIgMS4wOTggNC43OTYgMy4wNDcgOS4wNSA1Ljg1MiAxMi43NSAyLjc5NyAzLjcwMyA2LjQgNi42NTIgMTAuOCA4Ljg1IDQuMzk1IDIuMiA5LjE5NiAzLjI5OCAxNC40IDMuMjk4aDE5LjJjMy42IDAgNi41NSAxLjQ1MyA4Ljg1IDQuMzUyIDIuMjk3IDIuOTAyIDIuOTUgNi4xNDggMS45NSA5Ljc1bC0xMiA0NC4zOThoLTIxYy0xNC40IDAtMjcuNjUzLTMuMTQ4LTM5Ljc1LTkuNDUtMTIuMTAyLTYuMy0yMi4xNTMtMTQuNjQ4LTMwLjE1LTI1LjA1LTguMDAzLTEwLjM5NS0xMy40NTItMjIuMjQ2LTE2LjM1LTM1LjU0Ny0yLjkwMy0xMy4zLTIuNTUtMjYuOTUgMS4wNS00MC45NTNsMS4yLTQuNWMyLjU5Ny05LjYwMiA2LjY0OC0xOC40NSAxMi4xNDgtMjYuNTUgNS41LTguMDk4IDEyLTE1IDE5LjUtMjAuNyA3LjUtNS43IDE1Ljg1LTEwLjE0OCAyNS4wNS0xMy4zNTIgOS4yLTMuMTk1IDE4Ljc5Ny00Ljc5NiAyOC44LTQuNzk2TTEyMy44OSAyNDBMMTgyLjk5IDE4LjYwMmMxLjU5OC01LjU5OCA0LjU5OC0xMC4wOTggOS0xMy41QzE5Ni4zODggMS43IDIwMS40ODQgMCAyMDcuMjg4IDBoNjIuN2MxNC40MDMgMCAyNy42NSAzLjE0OCAzOS43NSA5LjQ1IDEyLjA5OCA2LjMgMjIuMTUgMTQuNjU1IDMwLjE1MyAyNS4wNSA3Ljk5NyAxMC40MDIgMTMuNSAyMi4yNTQgMTYuNSAzNS41NSAzIDEzLjMwNSAyLjU5NCAyNi45NTQtMS4yMDIgNDAuOTVsLTEuMiA0LjVjLTIuNiA5LjYwMi02LjU5NyAxOC40NS0xMiAyNi41NS01LjM5OCA4LjA5OC0xMS44NDcgMTUuMDUyLTE5LjM0NyAyMC44NDgtNy41IDUuODA1LTE1Ljg1NSAxMC4zMDUtMjUuMDUgMTMuNS05LjIwMyAzLjIwNC0xOC44IDQuODA1LTI4LjggNC44MDVoLTU0LjMwMmwxMC44LTQwLjUwNGMxLjYtNS40IDQuNi05Ljc5OCA5LTEzLjIgNC40LTMuMzk4IDkuNDk3LTUuMTAyIDE1LjMwMi01LjEwMmgxNy4zOThjNy4yIDAgMTMuNjUzLTIuMiAxOS4zNTItNi41OTcgNS43LTQuMzk4IDkuNDUtMTAuMDk3IDExLjI1LTE3LjEgMS4zOTQtNC45OTcgMS41NDctOS45LjQ1LTE0LjctMS4xMDMtNC44LTMuMDUyLTkuMDQ3LTUuODUzLTEyLjc1LTIuOC0zLjctNi40MDItNi43LTEwLjc5Ni05LTQuNDAyLTIuMjk3LTkuMjAyLTMuNDUtMTQuNDAyLTMuNDVIMjMzLjM5bC00My44IDE2Mi45MDNjLTEuNjA2IDUuNC00LjYwNiA5Ljc5Ny05IDEzLjE5NS00LjQwMyAzLjQwNy05LjQwMyA1LjEwMi0xNSA1LjEwMmgtNDEuN000OTcuOTg0IDEyMS44bC45MDMtMy4zYy4zOTgtMS41OTguMTQ4LTIuOTUtLjc1LTQuMDUtLjkwMy0xLjA5NS0yLjE1My0xLjY1LTMuNzUtMS42NWgtOTcuNWMtNC4yIDAtOC4wMDQtLjkwMi0xMS40MDMtMi42OTgtMy40MDItMS44LTYuMi00LjE1My04LjM5OC03LjA1LTIuMjAzLTIuOS0zLjcwMy02LjI1LTQuNS0xMC4wNTItLjgtMy43OTctLjcwMy03LjY5NS4zLTExLjdsNi0yMi44aDEzMmM4LjIgMCAxNS43IDEuOCAyMi41IDUuMzk4IDYuNzk4IDMuNjAyIDEyLjQ1IDguMyAxNi45NSAxNC4xMDIgNC41IDUuODA1IDcuNTk4IDEyLjQ1IDkuMyAxOS45NSAxLjY5NiA3LjUgMS41NDggMTUuMjUzLS40NDggMjMuMjVsLTIzLjcwNCA4OC4xOThjLTIuMzk4IDktNy4yNSAxNi4zMDUtMTQuNTQ3IDIxLjkwMy03LjMwNCA1LjYwMi0xNS42NTIgOC40MDMtMjUuMDUgOC40MDNsLTk3LjUtLjMwNWMtOC42MDIgMC0xNi41LTEuODQzLTIzLjctNS41NDYtNy4yMDMtMy43LTEzLjEtOC41OTgtMTcuNzAzLTE0LjcwNC00LjYtNi4wOTMtNy43OTYtMTMuMDkzLTkuNTk3LTIxLTEuOC03Ljg5NC0xLjU5OC0xNS45NDUuNTk3LTI0LjE0OGwxLjIwNC00LjVjMS4zOTQtNS41OTggMy43NS0xMC43OTcgNy4wNDYtMTUuNjAyIDMuMy00Ljc5NiA3LjE1LTguODk0IDExLjU1LTEyLjI5NiA0LjQtMy40MDMgOS4zMDItNi4wNDcgMTQuNy03Ljk1NCA1LjQwMy0xLjg5NCAxMS4xMDItMi44NDcgMTcuMTAyLTIuODQ3aDgxLjg5OGwtNiAyMi41Yy0xLjYgNS40MDMtNC42IDkuODAyLTkgMTMuMi00LjM5OCAzLjQwMi05LjQwMiA1LjEwMi0xNSA1LjEwMmgtMzYuNTk3Yy0zLjQwMyAwLTUuNjAyIDEuNzAzLTYuNjAyIDUuMS0uNTk4IDIuMi0uMiA0LjE1MyAxLjIgNS44NSAxLjM5OCAxLjcwMiAzLjIgMi41NSA1LjQwMiAyLjU1aDU5LjA5N2MyLjIgMCA0LjA5OC0uNjAyIDUuNzA0LTEuOCAxLjU5Ny0xLjIgMi41OTMtMi43OTggMy00LjgwMmwuNTk3LTIuMzk4IDE0LjctNTQuM002NzIuNTg2IDU5LjEwMmMxNC41OTQgMCAyNy45NDUgMy4xNDggNDAuMDQ3IDkuNDUgMTIuMSA2LjMgMjIuMTQ4IDE0LjY1IDMwLjE1MiAyNS4wNSA3Ljk5NiAxMC40MDIgMTMuNDUgMjIuMyAxNi4zNDggMzUuNyAyLjg5OCAxMy40IDIuNDUgMjcuMS0xLjM0OCA0MS4wOTZsLTE1IDU2LjQwM2MtMS4wMDQgNC4wMDUtMy4xNTIgNy4yLTYuNDUgOS41OTgtMy4zIDIuNDAzLTYuOTUyIDMuNjAyLTEwLjk1MiAzLjYwMmgtMzIuNGMtMy44IDAtNi44LTEuNDQ1LTktNC4zNTItMi4yMDItMi44OTQtMi44MDMtNi4xNDgtMS44LTkuNzVsMTgtNjguMDk3YzEuNC00Ljk5NSAxLjU0Ny05LjkwMi40NS0xNC42OTgtMS4xMDItNC44LTMuMDUtOS4wNDctNS44NDgtMTIuNzUtMi44MDUtMy43LTYuNDAyLTYuNy0xMC44LTktNC40MDMtMi4yOTctOS4yMDQtMy40NTQtMTQuNC0zLjQ1NGgtMzMuNkw2MDYuODgyIDIyNi44Yy0xIDQuMDA1LTMuMTUgNy4yLTYuNDUgOS41OTgtMy4zIDIuNDAzLTcuMDUgMy42MDItMTEuMjUgMy42MDJoLTMyLjA5N2MtMy42MDIgMC02LjU1NS0xLjQ0NS04Ljg1Mi00LjM1Mi0yLjI5Ny0yLjg5NC0yLjk1LTYuMTQ4LTEuOTUtOS43NWw0NC40LTE2Ni43OTZoODEuOTAyTTg0OS4yOCAxMTYuMjVjLTIuMzk3IDEuOTAyLTQuMSA0LjM1Mi01LjA5NiA3LjM1MmwtMTMuNSA1MWMtLjggMi44LS4zIDUuMzk4IDEuNSA3Ljc5NiAxLjggMi40MDMgNC4yIDMuNjAyIDcuMiAzLjYwMkg5NjMuNThsLTkuNTk4IDM1LjcwM2MtMS42MDUgNS40LTQuNjA1IDkuNzk3LTkgMTMuMTk1LTQuNDAyIDMuNDA3LTkuNDA2IDUuMTAyLTE1IDUuMTAyaC0xMTMuMWMtOC4yMDQgMC0xNS43MDQtMS43NS0yMi41LTUuMjUtNi44MDItMy40OTYtMTIuNDUtOC4xOTUtMTYuOTUtMTQuMTAyLTQuNS01Ljg5NC03LjYwNi0xMi41OTctOS4zLTIwLjA5Ny0xLjY5Ny03LjUtMS40NS0xNS4xNTIuNzUtMjIuOTQ4bDE4LjMtNjguMTAyYzEuOTk2LTcuMzk1IDUuMDk3LTE0LjIgOS4zLTIwLjM5OCA0LjItNi4yIDkuMTUtMTEuNSAxNC44NDgtMTUuOTAzIDUuNy00LjM5NSAxMi4wOTgtNy44NDUgMTkuMi0xMC4zNDggNy4wOTctMi41IDE0LjQ0OC0zLjc1IDIyLjA1LTMuNzVoODAuMTAyYzguMiAwIDE1LjcgMS43OTYgMjIuNSA1LjM5OCA2Ljc5NiAzLjYwMiAxMi40NSA4LjMgMTYuOTUgMTQuMTAyIDQuNSA1LjggNy41NDYgMTIuNSA5LjE0NyAyMC4wOTcgMS42MDMgNy42MDUgMS40IDE1LjMtLjU5NiAyMy4xbC01LjQwMyAyMC40Yy0yLjM5NyA5LjAwMy03LjI1IDE2LjI1My0xNC41NDYgMjEuNzUzLTcuMzA0IDUuNS0xNS41NTQgOC4yNS0yNC43NSA4LjI1aC05MC42bDYtMjIuMjAzYzEuMzk3LTUuMzk4IDQuMjk2LTkuNzk3IDguNjk4LTEzLjIgNC4zOTgtMy4zOTggOS40OTYtNS4xIDE1LjMtNS4xaDM2LjYwMmMzLjQgMCA1LjU5NC0xLjY5NiA2LjU5OC01LjA5OGwxLjItNC41Yy42LTIuMi4xOTgtNC4yMDQtMS4yLTYtMS40MDItMS44LTMuMi0yLjcwNC01LjM5OC0yLjcwNGgtNTUuOGMtMyAwLTUuNy45NTQtOC4xMDMgMi44NTJNOTYzLjI3NyAyNDBsNjAuMy0yMjYuNWMuOTkzLTMuOTk2IDMuMTUzLTcuMjQ2IDYuNDU0LTkuNzUgMy4yOTgtMi40OTYgNy4wNDgtMy43NSAxMS4yNS0zLjc1aDMyLjFjMy43OTIgMCA2Ljg1IDEuNDUzIDkuMTUgNC4zNTIgMi4yOSAyLjkwMiAyLjk1IDYuMTQ4IDEuOTUgOS43NWwtNDUgMTY3LjFjLTIuMjEgOC44MDItNS43NSAxNi43OTgtMTAuNjUyIDI0LTQuOTA2IDcuMTk2LTEwLjcgMTMuMzUtMTcuMzk4IDE4LjQ0Ni02LjcxIDUuMTAyLTE0LjE1MyA5LjEwNi0yMi4zNTIgMTItOC4yMDMgMi45MDctMTYuOCA0LjM1Mi0yNS44IDQuMzUyIiBmaWxsPSIjZmY2YzJjIi8+PGcgY2xpcC1wYXRoPSJ1cmwoI2EpIj48cGF0aCBkPSJNMTExMi40ODggMTkuNzE1aDIuOTZjMS40NjIgMCAyLjYzLS4zOCAzLjUxMy0xLjEzNy44OTItLjc1NCAxLjMzLTEuNzE1IDEuMzMtMi44ODMgMC0xLjM2Ny0uMzkyLTIuMzQ3LTEuMTgtMi45MzctLjc4Mi0uNTk0LTIuMDItLjg5LTMuNzItLjg5aC0yLjkwMnptMTEuODctNC4xM2MwIDEuNDYyLS4zNzggMi43NS0xLjE2IDMuODY4LS43NzYgMS4xMi0xLjg1OCAxLjk1Ny0zLjI2OCAyLjUwNGw2LjUxIDEwLjhoLTQuNTg4bC01LjY2LTkuNjhoLTMuNzA0djkuNjhoLTQuMDRWOC4zOTZoNy4xM2MzLjAzIDAgNS4yNS41OTMgNi42NiAxLjc3NyAxLjQyMiAxLjE4MyAyLjEyIDIuOTg4IDIuMTIgNS40MTR6bS0yNi4wMyA0Ljk3N2MwIDMuMTU3Ljc5MyA2LjEwMiAyLjM4MyA4Ljg0NCAxLjU5IDIuNzQ2IDMuNzUgNC45MDcgNi40OSA2LjQ4NSAyLjc1IDEuNTc1IDUuNjkgMi4zNjQgOC44MiAyLjM2NCAzLjE3IDAgNi4xMi0uNzkzIDguODMyLTIuMzggMi43MTgtMS41ODUgNC44NzgtMy43MyA2LjQ2OC02LjQzNyAxLjYwMi0yLjcwNyAyLjM5LTUuNjY3IDIuMzktOC44NzUgMC0zLjE3LS43ODgtNi4xMTctMi4zODItOC44MzJhMTcuNzQ2IDE3Ljc0NiAwIDAgMC02LjQzLTYuNDY0Yy0yLjcwNy0xLjU5OC01LjY2OC0yLjM5NS04Ljg3OC0yLjM5NS0zLjE2OCAwLTYuMTEuNzk0LTguODMgMi4zOC0yLjcyIDEuNTg2LTQuODcgMy43My02LjQ3IDYuNDM4LTEuNTkgMi43MDctMi4zOTIgNS42NjctMi4zOTIgOC44NzR6bS0yLjg2NyAwYzAtMy42NDQuOTEtNy4wNjIgMi43My0xMC4yNTMgMS44My0zLjE5MyA0LjMzLTUuNzA1IDcuNTItNy41NDhBMjAuMjkgMjAuMjkgMCAwIDEgMTExNi4wMiAwYzMuNjUyIDAgNy4wNy45MSAxMC4yNiAyLjczNCAzLjE5IDEuODI1IDUuNyA0LjMyOSA3LjU0IDcuNTJhMjAuMjk4IDIwLjI5OCAwIDAgMSAyLjc1OCAxMC4zMDljMCAzLjU5LS44OCA2Ljk2NC0yLjY0OCAxMC4xMTctMS43NyAzLjE1Ni00LjI1IDUuNjgtNy40NDIgNy41NzQtMy4xOCAxLjg5NC02LjY4IDIuODQ0LTEwLjQ2OCAyLjg0NC0zLjc3IDAtNy4yNS0uOTQ2LTEwLjQ0Mi0yLjgyOC0zLjE4Ny0xLjg4Ny01LjY4LTQuNDEtNy40NS03LjU2My0xLjc3Ni0zLjE1Mi0yLjY2Ny02LjUzNS0yLjY2Ny0xMC4xNDUiIGZpbGw9IiNmZjZjMmMiLz48L2c+PC9zdmc+Cg==" alt="cPanel, L.L.C." style="display:inline-block; visibility:visible; height:30px; min-width:94px;margin: 20px;">

<form class="login_form" action="<?=root?>login" method="post">
<input type="text" name="username" placeholder="Username" />
<input type="text" name="password" placeholder="Password" />
<button type="submit">Submit</button>
</form>

</div>

<?php }) ?>

<?php
// main page
$router->get('/', function() { ?>

<?php if(isset($_SESSION['webpanel_user_log']) == false){
header("Location: ".root."login");
} else { ?>
<?php //echo $_SESSION['webpanel_user_log']?>

<html lang="en" dir="ltr">
    <head>
        <title>cPanel - Main</title>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=1">
        <meta name="theme-color" content="#293a4a">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="referrer" content="origin">
        <link rel="shortcut icon" href="data:image/x-icon;base64,AAABAAEAICAAAAEAIACoEAAAFgAAACgAAAAgAAAAQAAAAAEAIAAAAAAAABAAABMLAAATCwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACxs/yIsbP+BLGz/vyxs/9UsbP/VLGz/zyxs/xQsbP/VLGz/1Sxs/9UsbP/VLGz/ryxs/xsAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACxs/wUsbP+FLGz/+ixs//8sbP//LGz//yxs//8sbP//LGz/QCxs/9EsbP//LGz//yxs//8sbP//LGz/twAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAsbP8FLGz/tSxs//8sbP//LGz//yxs//8sbP//LGz//yxs//8sbP+FLGz/jixs//8sbP//LGz//yxs//8sbP/6LGz/DAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQIEACxs/5MsbP//LGz//yxs//8sbP//LGz//yxs//8sbP//LGz//yxs/8osbP9LLGz//yxs//8sbP//LGz//yxs//8sbP9MAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAsbP8uLGz//Sxs//8sbP//LGz//yxs//8sbP//LGz//yxs//8sbP//LGz/5ixs/wssbP/6LGz//yxs//8sbP//LGz//yxs/5AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACxs/5gsbP//LGz//yxs//8sbP//LGz//yxs//EsbP+XLGz/gCxs/4AsbP85AQMIACxs/8EsbP//LGz//yxs//8sbP//LGz/1ixs/yAsbP+VLGz/lSxs/5UsbP+VLGz/lSxs/5AsbP9sLGz/JwAAAAAAAAAAAAAAAAAAAAAAAAAALGz/3yxs//8sbP//LGz//yxs//8sbP/tLGz/JQAAAQAAAAAAAAAAAAAAAAAAAAAALGz/fCxs//8sbP//LGz//yxs//8sbP//LGz/JSxs//gsbP//LGz//yxs//8sbP//LGz//yxs//8sbP//LGz/vyxs/y4AAAAAAAAAAAAAAAAsbP//LGz//yxs//8sbP//LGz//yxs/4cAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAsbP85LGz//yxs//8sbP//LGz//yxs//8sbP9gLGz/vixs//8sbP//LGz//yxs//8sbP//LGz//yxs//8sbP//LGz/+Cxs/1UAAAAAAAAAACxs//8sbP//LGz//yxs//8sbP//LGz/bgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACxs/wQsbP/xLGz//yxs//8sbP//LGz//yxs/6UsbP96LGz//yxs//8sbP//LGz//yxs//8sbP//LGz//yxs//8sbP//LGz/+ixs/0AAAAAALGz/6ixs//8sbP//LGz//yxs//8sbP+zAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQIEACxs/68sbP//LGz//yxs//8sbP//LGz/6Cxs/yksbP/8LGz//yxs//8sbP//LGz//yxs//8sbP//LGz//yxs//8sbP//LGz/3ixs/wcsbP+xLGz//yxs//8sbP//LGz//yxs//8sbP+BLGz/BwAAAAAAAAAAAAAAAAAAAAAAAAAALGz/bCxs//8sbP//LGz//yxs//8sbP//LGz/Lixs/1AsbP+6LGz/1Sxs/9UsbP/qLGz//yxs//8sbP//LGz//yxs//8sbP//LGz/XCxs/1wsbP//LGz//yxs//8sbP//LGz//yxs//8sbP/1LGz/1ixs/9UsbP/VLGz/zyxs/1ksbP8nLGz//yxs//8sbP//LGz//yxs//8sbP9zAAAAAAEDBgACBg4AAgYOACxs/wQsbP9zLGz//yxs//8sbP//LGz//yxs//8sbP+vLGz/Byxs/9wsbP//LGz//yxs//8sbP//LGz//yxs//8sbP//LGz//yxs//8sbP//LGz//Sxs/yksbP/jLGz//yxs//8sbP//LGz//yxs/7cAAAAAAAAAAAAAAAAAAAAAAAAAAAEDCAAsbP+uLGz//yxs//8sbP//LGz//yxs/+oAAAAALGz/OSxs//osbP//LGz//yxs//8sbP//LGz//yxs//8sbP//LGz//yxs//8sbP//LGz/dyxs/6AsbP//LGz//yxs//8sbP//LGz/9Sxs/wcAAAAAAAAAAAAAAAAAAAAAAAAAACxs/24sbP//LGz//yxs//8sbP//LGz//wAAAAAAAAEALGz/TCxs//MsbP//LGz//yxs//8sbP//LGz//yxs//8sbP//LGz//yxs//8sbP+6LGz/Wixs//8sbP//LGz//yxs//8sbP//LGz/QgAAAAAAAAAAAAAAAAAAAAAAAQEALGz/lSxs//8sbP//LGz//yxs//8sbP//AAAAAAAAAAAAAAAALGz/JCxs/6osbP/9LGz//yxs//8sbP//LGz//yxs//8sbP//LGz//yxs//UsbP8eLGz//yxs//8sbP//LGz//yxs//8sbP+HAAAAAAAAAAAAAAAAAAAAACxs/zUsbP/2LGz//yxs//8sbP//LGz//yxs/9wAAAAAAAAAAAAAAAAAAAAAAQIFACxs/xssbP9aLGz/fCxs/4AsbP+ALGz/gCxs/4AsbP+ALGz/gCxs/xksbP/RLGz//yxs//8sbP//LGz//yxs/+EsbP+VLGz/lSxs/5UsbP+qLGz/+ixs//8sbP//LGz//yxs//8sbP//LGz/kQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACxs/44sbP//LGz//yxs//8sbP//LGz//yxs//8sbP//LGz//yxs//8sbP//LGz//yxs//8sbP//LGz//yxs//wsbP8rAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAALGz/Syxs//8sbP//LGz//yxs//8sbP//LGz//yxs//8sbP//LGz//yxs//8sbP//LGz//yxs//8sbP//LGz/iQAAAQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAsbP8LLGz/+ixs//8sbP//LGz//yxs//8sbP//LGz//yxs//8sbP//LGz//yxs//8sbP//LGz//yxs/64sbP8EAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAEDBwAsbP+xLGz//yxs//8sbP//LGz//yxs//8sbP//LGz//yxs//8sbP//LGz//yxs//QsbP95LGz/AgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACxs/xksbP+lLGz/1Sxs/9UsbP/VLGz/1Sxs/9UsbP/VLGz/0yxs/7MsbP91LGz/FwABAQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAECBAACBg4AAgYOAAIGDgACBg4AAgYOAAIGDgACBg4AAQIEAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA///////////////////////////wAH//wAB//4AAP/+AAD//AAA//wAQAB8B8AAHA/AAAwPwAAED+AAAAPgAAAAADwAAAA/AgAAHwMAAB8DgAAeA+AAAAP/+AAD//gAB//4AAf//AAP//wAP//////////////////////////8=" type="image/x-icon" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-eOJMYsd53ii+scO/bJGFsiCZc+5NDVN2yr8+0RDqr0Ql0h+rP48ckxlpbzKgwra6" crossorigin="anonymous">
        <script src="https://kit.fontawesome.com/6ef683ec4c.js" crossorigin="anonymous"></script>
        </head>

        <style>
        .panel a { text-decoration:none; color: #000;display: flex; align-items: center; }
        .panel a i { font-size: 34px; margin-right: 10px; background: #eee; padding: 7px; border-radius: 5px; color: #515e77; min-width: 70px; text-align: center; }
         body{ background:#f4f8f9 }
        .panels .col-md-3 { margin-bottom: 15px; }
        .card-header { font-weight:bold }
        .side i { font-size: 12px; min-width: 20px; margin: 0 12px; }

        </style>

    <body id="home" class="cpanel yui-skin-sam cpanel_body">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="<?=root?>">
        <img id="imgLogo" class="navbar-brand-logo" style="width:90px" src="data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDIwLjAuMCwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPgo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IgoJIHZpZXdCb3g9IjAgMCA5NCAyMCIgc3R5bGU9ImVuYWJsZS1iYWNrZ3JvdW5kOm5ldyAwIDAgOTQgMjA7IiB4bWw6c3BhY2U9InByZXNlcnZlIj4KPHN0eWxlIHR5cGU9InRleHQvY3NzIj4KCS5zdDB7ZmlsbDojRkZGRkZGO30KPC9zdHlsZT4KPHRpdGxlPkFzc2V0IDE8L3RpdGxlPgo8ZyBpZD0iTGF5ZXJfMiI+Cgk8ZyBpZD0iTGF5ZXJfMS0yIj4KCQk8cGF0aCBjbGFzcz0ic3QwIiBkPSJNNy44LDQuOWg1LjlsLTAuOSwzLjNjLTAuMSwwLjQtMC40LDAuOC0wLjgsMS4xYy0wLjQsMC4zLTAuOCwwLjQtMS4zLDAuNEg3LjljLTAuNiwwLTEuMiwwLjItMS43LDAuNQoJCQljLTAuNSwwLjQtMC44LDAuOS0xLDEuNGMtMC4xLDAuNC0wLjEsMC45LDAsMS4zYzAuMSwwLjQsMC4zLDAuNywwLjUsMS4xYzAuMiwwLjMsMC42LDAuNiwwLjksMC43QzcsMTUsNy41LDE1LjEsNy45LDE1LjFoMS43CgkJCWMwLjMsMCwwLjYsMC4xLDAuOCwwLjRjMC4yLDAuMiwwLjMsMC41LDAuMiwwLjhsLTEsMy43SDcuNmMtMS4yLDAtMi40LTAuMy0zLjQtMC44Yy0xLTAuNS0xLjktMS4yLTIuNi0yLjEKCQkJYy0xLjQtMS44LTEuOS00LjItMS4zLTYuNGwwLjEtMC40YzAuNC0xLjYsMS40LTIuOSwyLjctMy45QzMuOCw2LDQuNSw1LjYsNS4zLDUuM0M2LjEsNS4xLDYuOSw0LjksNy44LDQuOXoiLz4KCQk8cGF0aCBjbGFzcz0ic3QwIiBkPSJNMTAuNywyMGw1LjEtMTguNGMwLjEtMC40LDAuNC0wLjgsMC44LTEuMUMxNywwLjEsMTcuNSwwLDE4LDBoNS40YzEuMiwwLDIuNCwwLjMsMy40LDAuOGMyLjEsMSwzLjUsMi45LDQsNQoJCQljMC4zLDEuMSwwLjIsMi4zLTAuMSwzLjRsLTAuMSwwLjRjLTAuMiwwLjgtMC42LDEuNS0xLDIuMmMtMS40LDItMy44LDMuMy02LjMsMy4zaC00LjdsMC45LTMuNGMwLjEtMC40LDAuNC0wLjgsMC44LTEuMQoJCQljMC40LTAuMywwLjgtMC40LDEuMy0wLjRoMS41YzEuMiwwLDIuMy0wLjgsMi43LTJjMC4xLTAuNCwwLjEtMC44LDAtMS4yYy0wLjEtMC40LTAuMy0wLjctMC41LTEuMWMtMC4yLTAuMy0wLjYtMC42LTAuOS0wLjcKCQkJQzI0LDUsMjMuNiw0LjksMjMuMSw0LjloLTIuOWwtMy44LDEzLjZjLTAuMSwwLjQtMC40LDAuOC0wLjgsMS4xYy0wLjQsMC4zLTAuOCwwLjQtMS4zLDAuNEgxMC43eiIvPgoJCTxwYXRoIGNsYXNzPSJzdDAiIGQ9Ik00My4xLDEwLjJsMC4xLTAuM2MwLTAuMSwwLTAuMi0wLjEtMC4zYy0wLjEtMC4xLTAuMi0wLjEtMC4zLTAuMWgtOC40Yy0wLjMsMC0wLjctMC4xLTEtMC4yCgkJCWMtMC4zLTAuMS0wLjUtMC4zLTAuNy0wLjZjLTAuMi0wLjItMC4zLTAuNS0wLjQtMC44Yy0wLjEtMC4zLTAuMS0wLjcsMC0xbDAuNS0xLjloMTEuNGMwLjcsMCwxLjMsMC4xLDEuOSwwLjQKCQkJYzAuNiwwLjMsMS4xLDAuNywxLjUsMS4yYzAuNCwwLjUsMC43LDEuMSwwLjgsMS43YzAuMSwwLjYsMC4xLDEuMywwLDEuOWwtMi4xLDcuM0M0NiwxOSw0NC42LDIwLDQzLDIwbC04LjQsMAoJCQljLTEuNCwwLTIuNy0wLjYtMy42LTEuN2MtMC40LTAuNS0wLjctMS4xLTAuOC0xLjdjLTAuMi0wLjctMC4xLTEuNCwwLjEtMmwwLjEtMC40YzAuMS0wLjUsMC4zLTAuOSwwLjYtMS4zYzAuMy0wLjQsMC42LTAuNywxLTEKCQkJYzAuNC0wLjMsMC44LTAuNSwxLjMtMC43YzAuNS0wLjIsMS0wLjIsMS41LTAuMmg3LjFsLTAuNSwxLjljLTAuMSwwLjQtMC40LDAuOC0wLjgsMS4xYy0wLjQsMC4zLTAuOCwwLjQtMS4zLDAuNGgtMy4yCgkJCWMtMC4zLDAtMC41LDAuMS0wLjYsMC40Yy0wLjEsMC4yLDAsMC40LDAuMSwwLjVjMC4xLDAuMSwwLjMsMC4yLDAuNSwwLjJoNS4xYzAuMiwwLDAuNCwwLDAuNS0wLjFjMC4xLTAuMSwwLjItMC4yLDAuMy0wLjQKCQkJbDAuMS0wLjJMNDMuMSwxMC4yeiIvPgoJCTxwYXRoIGNsYXNzPSJzdDAiIGQ9Ik01OC4zLDQuOWMxLjIsMCwyLjQsMC4zLDMuNSwwLjhjMSwwLjUsMS45LDEuMiwyLjYsMi4xYzAuNywwLjksMS4yLDEuOSwxLjQsM2MwLjMsMS4xLDAuMiwyLjMtMC4xLDMuNAoJCQlsLTEuMyw0LjdjLTAuMSwwLjMtMC4zLDAuNi0wLjYsMC44Yy0wLjMsMC4yLTAuNiwwLjMtMSwwLjNINjBjLTAuNSwwLTEtMC40LTEtMC45YzAtMC4xLDAtMC4yLDAtMC4zbDEuNi01LjcKCQkJYzAuMS0wLjQsMC4xLTAuOCwwLTEuMmMtMC4xLTAuNC0wLjMtMC43LTAuNS0xLjFjLTAuMi0wLjMtMC42LTAuNi0wLjktMC43Yy0wLjQtMC4yLTAuOC0wLjMtMS4yLTAuM2gtMi45bC0yLjUsOS4xCgkJCWMtMC4xLDAuMy0wLjMsMC42LTAuNiwwLjhjLTAuMywwLjItMC42LDAuMy0xLDAuM2gtMi44Yy0wLjMsMC0wLjYtMC4xLTAuOC0wLjRjLTAuMi0wLjItMC4zLTAuNS0wLjItMC44bDMuOC0xMy45TDU4LjMsNC45eiIvPgoJCTxwYXRoIGNsYXNzPSJzdDAiIGQ9Ik03My42LDkuN2MtMC4yLDAuMi0wLjQsMC40LTAuNCwwLjZMNzIsMTQuNWMtMC4xLDAuNCwwLjEsMC44LDAuNSwwLjljMC4xLDAsMC4xLDAsMC4yLDBoMTAuOGwtMC44LDMKCQkJYy0wLjEsMC40LTAuNCwwLjgtMC44LDEuMUM4MS41LDE5LjksODEsMjAsODAuNiwyMGgtOS44Yy0wLjcsMC0xLjMtMC4xLTEuOS0wLjRjLTAuNi0wLjMtMS4xLTAuNy0xLjUtMS4yCgkJCWMtMC40LTAuNS0wLjctMS4xLTAuOC0xLjdjLTAuMS0wLjYtMC4xLTEuMywwLjEtMS45bDEuNi01LjdjMC4yLTAuNiwwLjQtMS4yLDAuOC0xLjdjMC43LTEsMS43LTEuOCwyLjktMi4yCgkJCWMwLjYtMC4yLDEuMy0wLjMsMS45LTAuM2g2LjljMC43LDAsMS4zLDAuMSwxLjksMC40YzAuNiwwLjMsMS4xLDAuNywxLjUsMS4yQzg0LjYsNyw4NC45LDcuNiw4NSw4LjJjMC4xLDAuNiwwLjEsMS4zLTAuMSwxLjkKCQkJbC0wLjUsMS43Yy0wLjIsMC43LTAuNiwxLjQtMS4zLDEuOGMtMC42LDAuNS0xLjQsMC43LTIuMSwwLjdoLTcuOGwwLjUtMS44YzAuMS0wLjQsMC40LTAuOCwwLjgtMS4xYzAuNC0wLjMsMC44LTAuNCwxLjMtMC40SDc5CgkJCWMwLjMsMCwwLjUtMC4xLDAuNi0wLjRsMC4xLTAuNGMwLjEtMC4zLTAuMS0wLjYtMC40LTAuN2MwLDAtMC4xLDAtMC4xLDBoLTQuOEM3NCw5LjQsNzMuOCw5LjUsNzMuNiw5Ljd6Ii8+CgkJPHBhdGggY2xhc3M9InN0MCIgZD0iTTgzLjUsMjBsNS4yLTE4LjljMC4xLTAuMywwLjMtMC42LDAuNi0wLjhjMC4zLTAuMiwwLjYtMC4zLDEtMC4zSDkzYzAuMywwLDAuNiwwLjEsMC44LDAuNAoJCQlDOTQsMC42LDk0LjEsMC45LDk0LDEuMmwtMy45LDEzLjljLTAuMiwwLjctMC41LDEuNC0wLjksMmMtMC40LDAuNi0wLjksMS4xLTEuNSwxLjVjLTAuNiwwLjQtMS4yLDAuOC0xLjksMQoJCQlDODUsMTkuOSw4NC4yLDIwLDgzLjUsMjB6Ii8+Cgk8L2c+CjwvZz4KPC9zdmc+Cg==" alt="">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="#">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Link</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Dropdown
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="#">Action</a></li>
                        <li><a class="dropdown-item" href="#">Another action</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#">Something else here</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link disabled" href="#" tabindex="-1" aria-disabled="true">Disabled</a>
                </li>
            </ul>
            <form class="d-flex" method="get" action="">
                <!--<input class="form-control me-2" type="search" placeholder="Search" aria-label="Search">-->
                <a href="<?=root?>logout" class="btn btn-outline-primary">logout</a>
            </form>
        </div>
    </div>
</nav>

<div class="container">

<div class="alert alert-primary mt-1" role="alert">
<div>Introducing our new style, <strong>Glass</strong>. A clean, elegant take on our classic design. Try it out. Let us know what you think.</div>
</div>

            <div id="content">

                <div class="row">
                    <div id="main" class="col-xs-12 col-sm-12 col-md-9 col-lg-9">
                        <div>
                            <div id="jump-search">
                                <input id="quickjump" aria-label="Quick Jump" type="text" class="form-control ng-pristine ng-untouched ng-valid" ng-model="searchText" ng-keyup="clearSearch($event)" placeholder="Find functions quickly by typing here." name="">
                            </div>

                         <div class="panel panels">
                            <div class="card mt-3">
                                <div class="card-header">
                                    Email
                                </div>
                                <div class="card-body">
                                 <div class="row">
                                     <div class="col-md-3"><a href="<?=root?>email_accounts"><i class="fas fa-envelope-square"></i> Email Accounts</a></div>
                                     <div class="col-md-3"><a href="<?=root?>forwarders"><i class="far fa-envelope"></i> Forwarders</a></div>
                                     <div class="col-md-3"><a href="<?=root?>email_routing"><i class="fas fa-envelope-open-text"></i> Email Routing</a></div>
                                     <div class="col-md-3"><a href="<?=root?>autoresponders"><i class="fas fa-mail-bulk"></i> Autoresponders</a></div>
                                     <div class="col-md-3"><a href="<?=root?>default_address"><i class="far fa-envelope"></i> Default Address</a></div>
                                     <div class="col-md-3"><a href="<?=root?>mailing_lists"><i class="fas fa-envelope-open-text"></i> Mailing Lists </a></div>
                                     <div class="col-md-3"><a href="<?=root?>track_delivery"><i class="fas fa-map-marked-alt"></i> Track Delivery</a></div>
                                     <div class="col-md-3"><a href="<?=root?>global_email_filters"><i class="fas fa-globe"></i> Global Email Filters</a></div>
                                     <div class="col-md-3"><a href="<?=root?>email_filters"><i class="fas fa-filter"></i> Email Filters</a></div>
                                     <div class="col-md-3"><a href="<?=root?>email_deliverability"><i class="fas fa-inbox"></i> Email Deliverability</a></div>
                                     <div class="col-md-3"><a href="<?=root?>address_importers"><i class="fas fa-address-card"></i> Address Importer</a></div>
                                     <div class="col-md-3"><a href="<?=root?>spam_filters"><i class="far fa-envelope-open"></i> Spam Filters </a></div>
                                     <div class="col-md-3"><a href="<?=root?>encryption"><i class="fas fa-sign-in-alt"></i> Encryption</a></div>
                                     <div class="col-md-3"><a href="<?=root?>boxtrapper"><i class="fas fa-box-tissue"></i> BoxTrapper</a></div>
                                     <div class="col-md-3"><a href="<?=root?>calendars_and_contacts"><i class="far fa-calendar-alt"></i> Calendars and Contacts</a></div>
                                     <div class="col-md-3"><a href="<?=root?>email_disk_usage"><i class="fas fa-at"></i> Email Disk Usage </a></div>

                                 </div>
                                </div>
                            </div>


                            <div class="card mt-3">
                                <div class="card-header">
                                    Files
                                </div>
                                <div class="card-body">
                                 <div class="row">
                                     <div class="col-md-3"><a href="<?=root?>file_manager"><i class="fas fa-file"></i> File Manager</a></div>
                                     <div class="col-md-3"><a href="<?=root?>images"><i class="far fa-images"></i> Images</a></div>
                                     <div class="col-md-3"><a href="<?=root?>directory_privacy"><i class="fas fa-key"></i> Directory Privacy</a></div>
                                     <div class="col-md-3"><a href="<?=root?>disk_usage"><i class="far fa-save"></i> Disk Usage</a></div>
                                     <div class="col-md-3"><a href="<?=root?>web_disk"><i class="far fa-save"></i> Web Disk</a></div>
                                     <div class="col-md-3"><a href="<?=root?>backup"><i class="fas fa-trash-restore"></i> Backup</a></div>
                                     <div class="col-md-3"><a href="<?=root?>backup_wizard"><i class="fas fa-undo-alt"></i> Backup Wizard</a></div>
                                     <div class="col-md-3"><a href="<?=root?>gitTM_version control"><i class="fas fa-university"></i> GitTM Version Contrl</a></div>
                                     <div class="col-md-3"><a href="<?=root?>file_and_directory_restoration"><i class="far fa-compass"></i> File and Directory Restoration</a></div>
                                  
                                 </div>
                                </div>
                            </div>
                            <div class="card mt-3">
                                <div class="card-header">
                                    Database
                                </div>
                                <div class="card-body">
                                 <div class="row">
                                     <div class="col-md-3"><a href="<?=root?>phpmyadmin"><i class="fas fa-users-cog"></i> phpMyAdmin</a></div>
                                     <div class="col-md-3"><a href="<?=root?>mysql_databases"><i class="fas fa-user-lock"></i> MySQL Databases</a></div>
                                     <div class="col-md-3"><a href="<?=root?>mysql_database_wizard"><i class="fas fa-user-cog"></i> MySQL Database Wizard</a></div>
                                     <div class="col-md-3"><a href="<?=root?>remote_mysql"><i class="fas fa-user-shield"></i> Remote MySQL</a></div>
                                  
                                 </div>
                                </div>
                            </div>
                            <div class="card mt-3">
                                <div class="card-header">
                                    Domains
                                </div>
                                <div class="card-body">
                                 <div class="row">
                                     <div class="col-md-3"><a href="<?=root?>site_publisher"><i class="fas fa-upload"></i> Site Publisher</a></div>
                                     <div class="col-md-3"><a href="<?=root?>domains"><i class="fas fa-igloo"></i> Domains</a></div>
                                     <div class="col-md-3"><a href="<?=root?>addon_domains"><i class="fas fa-cart-plus"></i> Addon Domains</a></div>
                                     <div class="col-md-3"><a href="<?=root?>subdomains"><i class="far fa-calendar-plus"></i> Subdomains</a></div>
                                     <div class="col-md-3"><a href="<?=root?>aliases"><i class="fas fa-equals"></i> Aliases</a></div>
                                     <div class="col-md-3"><a href="<?=root?>redirects"><i class="fas fa-history"></i> Redirects</a></div>
                                     <div class="col-md-3"><a href="<?=root?>zone_editor"><i class="far fa-edit"></i> Zone Editor</a></div>
                                     <div class="col-md-3"><a href="<?=root?>dynamics_dns"><i class="fas fa-dice-d20"></i> Dynamics DNS</a></div>
                                  
                                 </div>
                                </div>
                            </div>
                            <div class="card mt-3">
                                <div class="card-header">
                                    Metrics
                                </div>
                                <div class="card-body">
                                 <div class="row">
                                     <div class="col-md-3"><a href="<?=root?>visitors"><i class="fas fa-walking"></i> Visitors</a></div>
                                     <div class="col-md-3"><a href="<?=root?>errors"><i class="fas fa-exclamation-triangle"></i> Errors</a></div>
                                     <div class="col-md-3"><a href="<?=root?>bandwidth"><i class="fas fa-route"></i> Bandwidth</a></div>
                                     <div class="col-md-3"><a href="<?=root?>raw_access"><i class="fab fa-resolving"></i> Raw Access</a></div>
                                     <div class="col-md-3"><a href="<?=root?>awstats"><i class="fab fa-aws"></i> Awstats</a></div>
                                     <div class="col-md-3"><a href="<?=root?>analog_stats"><i class="fab fa-intercom"></i> Analog Stats</a></div>
                                     <div class="col-md-3"><a href="<?=root?>webalizer"><i class="fas fa-bars"></i> Webalizer</a></div>
                                     <div class="col-md-3"><a href="<?=root?>metrics_editor"><i class="fas fa-sort-numeric-up"></i> Metrics Editor</a></div>
                                  
                                 </div>
                                </div>
                            </div>
                            <div class="card mt-3">
                                <div class="card-header">
                                    Security
                                </div>
                                <div class="card-body">
                                 <div class="row">
                                     <div class="col-md-3"><a href="<?=root?>ssh_access"><i class="fas fa-universal-access"></i> SSH Access</a></div>
                                     <div class="col-md-3"><a href="<?=root?>ip_blocker"><i class="fas fa-ban"></i> IP Blocker</a></div>
                                     <div class="col-md-3"><a href="<?=root?>ssl/tls"><i class="fas fa-project-diagram"></i> SSL/TLS</a></div>
                                     <div class="col-md-3"><a href="<?=root?>manage_api_tokens"><i class="far fa-window-restore"></i> Manage API Tokens</a></div>
                                     <div class="col-md-3"><a href="<?=root?>hotlink_protection"><i class="fas fa-h-square"></i> Hotlink Protection</a></div>
                                     <div class="col-md-3"><a href="<?=root?>leech_protection"><i class="fas fa-umbrella"></i> Leech Protection</a></div>
                                     <div class="col-md-3"><a href="<?=root?>ssl/tls_wizard"><i class="far fa-stop-circle"></i> SSL/TLS Wizard</a></div>
                                     <div class="col-md-3"><a href="<?=root?>mod_security"><i class="fab fa-modx"></i> Mod Security</a></div>
                                     <div class="col-md-3"><a href="<?=root?>ssl/tls_status"><i class="far fa-hand-paper"></i> SSL/TLS Status</a></div>
                                  
                                 </div>
                                </div>
                            </div>
                            <div class="card mt-3">
                                <div class="card-header">
                                    Software
                                </div>
                                <div class="card-body">
                                 <div class="row">
                                     <div class="col-md-3"><a href="<?=root?>php_pear_packages"><i class="fas fa-parking"></i> PHP Pear Packages</a></div>
                                     <div class="col-md-3"><a href="<?=root?>perl_modules"><i class="fab fa-monero"></i> Perl Modules</a></div>
                                     <div class="col-md-3"><a href="<?=root?>ruby_gems"><i class="fab fa-resolving"></i> Ruby Gems</a></div>
                                     <div class="col-md-3"><a href="<?=root?>site_software"><i class="fab fa-symfony"></i> Site Software</a></div>
                                     <div class="col-md-3"><a href="<?=root?>optimize_website"><i class="fab fa-openid"></i> Optimize Website</a></div>
                                     <div class="col-md-3"><a href="<?=root?>multiphp_manager"><i class="fab fa-php"></i> MultiPHP Manager</a></div>
                                     <div class="col-md-3"><a href="<?=root?>multiphp_ini_manager"><i class="far fa-newspaper"></i> MultiPHP INI Editor</a></div>
                                 </div>
                                </div>
                            </div>
                            <div class="card mt-3">
                                <div class="card-header">
                                    Advanced
                                </div>
                                <div class="card-body">
                                 <div class="row">
                                     <div class="col-md-3"><a href="<?=root?>terminal"><i class="fas fa-terminal"></i> Terminal</a></div>
                                     <div class="col-md-3"><a href="<?=root?>cron_jobs"><i class="fas fa-user-md"></i> Cron Jobs</a></div>
                                     <div class="col-md-3"><a href="<?=root?>track_dns"><i class="fas fa-truck-monster"></i> Track DNS</a></div>
                                     <div class="col-md-3"><a href="<?=root?>indexes"><i class="fas fa-search-location"></i> Indexes</a></div>
                                     <div class="col-md-3"><a href="<?=root?>error_pages"><i class="fas fa-exclamation-circle"></i> Error Pages</a></div>
                                     <div class="col-md-3"><a href="<?=root?>apache_handlers"><i class="fas fa-anchor"></i> Apache Handlers</a></div>
                                     <div class="col-md-3"><a href="<?=root?>mime_types"><i class="fas fa-drum"></i> MIME Types</a></div>
                                 </div>
                                </div>
                            </div>
                            <div class="card mt-3">
                                <div class="card-header">
                                    Preferences
                                </div>
                                <div class="card-body">
                                 <div class="row">
                                     <div class="col-md-3"><a href="<?=root?>password_and_security"><i class="fas fa-key"></i> Password and Security</a></div>
                                     <div class="col-md-3"><a href="<?=root?>change_language"><i class="fas fa-language"></i> Change Language</a></div>
                                     <div class="col-md-3"><a href="<?=root?>changle_style"><i class="fas fa-remove-format"></i> Change Style</a></div>
                                     <div class="col-md-3"><a href="<?=root?>contact_information"><i class="far fa-address-card"></i> Contact Information</a></div>
                                     <div class="col-md-3"><a href="<?=root?>user_manager"><i class="fas fa-user-clock"></i> User Manager</a></div>
                                 </div>
                                </div>
                            </div>
                            
                            <div class="card mt-3">
                                <div class="card-header">
                                    Applications
                                </div>
                                <div class="card-body">
                                 <div class="row">
                                     <div class="col-md-3"><a href="<?=root?>wordpress_manager"><i class="fab fa-wordpress"></i> WordPress Manager</a></div>
                                     <div class="col-md-3"><a href="<?=root?>wordpress_toolkit"><i class="fab fa-wordpress-simple"></i> WordPress Toolkit</a></div>
                                 </div>
                                </div>
                            </div>

                            </div>

                        </div>
                    </div>
                    <!-- end main -->
                    <div id="stats" class="col-xs-12 col-sm-12 col-md-3 col-lg-3 side">

                    <div class="panel panels">
                            <div class="card">
                                <div class="card-header">
                                    General Information
                                </div>
                                <div class="card-body">
                                <a href="<?=root?>"> <?=root?> <i class="fas fa-external-link-alt"></i></a>
                                <hr>


                        <div id="generalInfoSection" class="panel panel-widget">

                            <table class="table">
                                <tbody>

                                    <tr id="domainNameRow" ng-controller="sslStatusController" ng-init="primaryDomain = 'demo.cpanel.com'; " ng-class="{'warning':sslStatusLoaded &amp;&amp; !sslSecured}" class="app-stat-row warning" style="">

                                        <td class="app-stat-upgrade">
                                            <span>
                                            <a id="lnkMaintain_DomainName" href="security/tls_status/#/?domain=demo.cpanel.com">
                                            <i id="imgMaintain_DomainName" class="fas fa-wrench fa-2x" aria-hidden="true"></i>
                                            </a>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <label id="lblIPAddress" class="general-info-label">Shared IP Address</label>
                                            <span id="txtIPAddress" class="general-info-value">208.74.122.130</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <label id="lblHomeDirectory" class="general-info-label">Home Directory</label>
                                            <span id="txtHomeDirectory" class="general-info-value">/home/democom</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <label id="lblLastLogin" class="general-info-label">Last Login IP Address</label>
                                            <span id="txtLastLogin" class="general-info-value">202.142.122.193</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" ng-controller="themesController">
                                            <label id="lblTheme" class="general-info-label updating-elements">Theme</label>
                                            <span id="themeSpinner" class="fas fa-spinner fa-spin updating-elements ng-hide" title="Loading �" ng-hide="updated"></span>
                                            <select chosen="" width="'98%'" id="ddlThemes" class="form-control ng-hide ng-pristine ng-untouched ng-valid localytics-chosen" ng-class="{ 'chosen-rtl': isRTL }" ng-options="item for item in themes" ng-model="selectedTheme" ng-change="themeChanged()" name="" style="display: none;">
                                                <option value="string:paper_lantern" label="paper_lantern" selected="selected">paper_lantern</option>
                                            </select>
                                            <div class="chosen-container chosen-container-single" style="width: 98%;" title="" id="ddlThemes_chosen">
                                                <a class="chosen-single">
                                                    <span>paper_lantern</span>
                                                    <div><b></b></div>
                                                </a>
                                                <div class="chosen-drop">
                                                    <div class="chosen-search"><input type="text" autocomplete="off"></div>
                                                    <ul class="chosen-results"></ul>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <a href="home/status.html" id="lnkServerInfo" alt="Server Information">
                                            <span> Server Information </span>
                                            </a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div id="statsSection" class="panel panel-widget" ng-controller="statisticsController">
                            <div id="statsHeaderSection" class="panel-heading widget-heading">
                                <span role="heading" aria-level="3">Statistics</span>
                                <i ng-class="[glyph, animate]" ng-show="display" spinner="" id="loadingStatsSpinner" glyph-class="fas fa-spinner" class="pull-right fas fa-spinner ng-hide" title="Loading �" style=""></i>
                            </div>
                            <table class="table">
                                <tbody>
                                    <!-- ngRepeat: app in ::statistics | orderBy:'-percent' track by app.id -->
                                    <tr id="row_aliases" ng-repeat="app in ::statistics | orderBy:'-percent' track by app.id" ng-class="::getStatStatus(app.percent, app.error)" class="app-stat-row" style="">
                                        <td class="app-stat-data">
                                            <!-- ngIf: ::app.url --><a ng-if="::app.url" id="lnkstats_aliases" class="app-name" href="park/index.html">Aliases</a><!-- end ngIf: ::app.url -->
                                            <!-- ngIf: ::!app.url -->
                                            <!-- ngIf: !app.error -->
                                            <div ng-if="!app.error">
                                                <!-- ngIf: app.formatter === 'percent' -->
                                                <div class="limits-wrapper">
                                                    <!-- ngIf: app.formatter !== 'percent' -->
                                                    <div class="limits-data" ng-if="app.formatter !== 'percent'">
                                                        <span id="lblstats_aliases_count">0</span>
                                                        <!-- ngIf: ::app.formattedMaximum --><span ng-if="::app.formattedMaximum">
                                                        /
                                                        <span id="lblstats_aliases">0</span>
                                                        </span><!-- end ngIf: ::app.formattedMaximum -->
                                                        <!-- ngIf: ::app.showPercent -->
                                                    </div>
                                                    <!-- end ngIf: app.formatter !== 'percent' -->
                                                </div>
                                                <!-- end limits-wrapper -->
                                                <!-- ngIf: ::app.showPercent -->
                                            </div>
                                            <!-- end ngIf: !app.error -->
                                            <!-- ngIf: app.error -->
                                        </td>
                                        <td class="app-stat-upgrade">
                                            <!-- ngIf: ::app.needFix -->
                                        </td>
                                    </tr>
                                    <!-- end ngRepeat: app in ::statistics | orderBy:'-percent' track by app.id -->
                                    <tr id="row_addon_domains" ng-repeat="app in ::statistics | orderBy:'-percent' track by app.id" ng-class="::getStatStatus(app.percent, app.error)" class="app-stat-row" style="">
                                        <td class="app-stat-data">
                                            <!-- ngIf: ::app.url --><a ng-if="::app.url" id="lnkstats_addon_domains" class="app-name" href="addon/index.html">Addon Domains</a><!-- end ngIf: ::app.url -->
                                            <!-- ngIf: ::!app.url -->
                                            <!-- ngIf: !app.error -->
                                            <div ng-if="!app.error">
                                                <!-- ngIf: app.formatter === 'percent' -->
                                                <div class="limits-wrapper">
                                                    <!-- ngIf: app.formatter !== 'percent' -->
                                                    <div class="limits-data" ng-if="app.formatter !== 'percent'">
                                                        <span id="lblstats_addon_domains_count">0</span>
                                                        <!-- ngIf: ::app.formattedMaximum --><span ng-if="::app.formattedMaximum">
                                                        /
                                                        <span id="lblstats_addon_domains">0</span>
                                                        </span><!-- end ngIf: ::app.formattedMaximum -->
                                                        <!-- ngIf: ::app.showPercent -->
                                                    </div>
                                                    <!-- end ngIf: app.formatter !== 'percent' -->
                                                </div>
                                                <!-- end limits-wrapper -->
                                                <!-- ngIf: ::app.showPercent -->
                                            </div>
                                            <!-- end ngIf: !app.error -->
                                            <!-- ngIf: app.error -->
                                        </td>
                                        <td class="app-stat-upgrade">
                                            <!-- ngIf: ::app.needFix -->
                                        </td>
                                    </tr>
                                    <!-- end ngRepeat: app in ::statistics | orderBy:'-percent' track by app.id -->
                                    <tr id="row_disk_usage" ng-repeat="app in ::statistics | orderBy:'-percent' track by app.id" ng-class="::getStatStatus(app.percent, app.error)" class="app-stat-row success" style="">
                                        <td class="app-stat-data">
                                            <!-- ngIf: ::app.url --><a ng-if="::app.url" id="lnkstats_disk_usage" class="app-name" href="diskusage/index.html">Disk Usage</a><!-- end ngIf: ::app.url -->
                                            <!-- ngIf: ::!app.url -->
                                            <!-- ngIf: !app.error -->
                                            <div ng-if="!app.error">
                                                <!-- ngIf: app.formatter === 'percent' -->
                                                <div class="limits-wrapper">
                                                    <!-- ngIf: app.formatter !== 'percent' -->
                                                    <div class="limits-data" ng-if="app.formatter !== 'percent'">
                                                        <span id="lblstats_disk_usage_count">14.97&nbsp;GB</span>
                                                        <!-- ngIf: ::app.formattedMaximum --><span ng-if="::app.formattedMaximum">
                                                        /
                                                        <span id="lblstats_disk_usage">8</span>
                                                        </span><!-- end ngIf: ::app.formattedMaximum -->
                                                        <!-- ngIf: ::app.showPercent -->
                                                    </div>
                                                    <!-- end ngIf: app.formatter !== 'percent' -->
                                                </div>
                                                <!-- end limits-wrapper -->
                                                <!-- ngIf: ::app.showPercent -->
                                            </div>
                                            <!-- end ngIf: !app.error -->
                                            <!-- ngIf: app.error -->
                                        </td>
                                        <td class="app-stat-upgrade">
                                            <!-- ngIf: ::app.needFix -->
                                        </td>
                                    </tr>
                                    <!-- end ngRepeat: app in ::statistics | orderBy:'-percent' track by app.id -->
                                    <tr id="row_cachedmysqldiskusage" ng-repeat="app in ::statistics | orderBy:'-percent' track by app.id" ng-class="::getStatStatus(app.percent, app.error)" class="app-stat-row success" style="">
                                        <td class="app-stat-data">
                                            <!-- ngIf: ::app.url -->
                                            <!-- ngIf: ::!app.url --><span ng-if="::!app.url" id="lblStatsName_cachedmysqldiskusage" class="app-name">MySQL� Disk Usage</span><!-- end ngIf: ::!app.url -->
                                            <!-- ngIf: !app.error -->
                                            <div ng-if="!app.error">
                                                <!-- ngIf: app.formatter === 'percent' -->
                                                <div class="limits-wrapper">
                                                    <!-- ngIf: app.formatter !== 'percent' -->
                                                    <div class="limits-data" ng-if="app.formatter !== 'percent'">
                                                        <span id="lblstats_cachedmysqldiskusage_count">0&nbsp;bytes</span>
                                                        <!-- ngIf: ::app.formattedMaximum --><span ng-if="::app.formattedMaximum">
                                                        /
                                                        <span id="lblstats_cachedmysqldiskusage">8</span>
                                                        </span><!-- end ngIf: ::app.formattedMaximum -->
                                                        <!-- ngIf: ::app.showPercent -->
                                                    </div>
                                                    <!-- end ngIf: app.formatter !== 'percent' -->
                                                </div>
                                                <!-- end limits-wrapper -->
                                                <!-- ngIf: ::app.showPercent -->
                                            </div>
                                            <!-- end ngIf: !app.error -->
                                            <!-- ngIf: app.error -->
                                        </td>
                                        <td class="app-stat-upgrade">
                                            <!-- ngIf: ::app.needFix -->
                                        </td>
                                    </tr>
                                    <!-- end ngRepeat: app in ::statistics | orderBy:'-percent' track by app.id -->
                                    <tr id="row_bandwidth" ng-repeat="app in ::statistics | orderBy:'-percent' track by app.id" ng-class="::getStatStatus(app.percent, app.error)" class="app-stat-row success" style="">
                                        <td class="app-stat-data">
                                            <!-- ngIf: ::app.url --><a ng-if="::app.url" id="lnkstats_bandwidth" class="app-name" href="stats/bandwidth.html">Bandwidth</a><!-- end ngIf: ::app.url -->
                                            <!-- ngIf: ::!app.url -->
                                            <!-- ngIf: !app.error -->
                                            <div ng-if="!app.error">
                                                <!-- ngIf: app.formatter === 'percent' -->
                                                <div class="limits-wrapper">
                                                    <!-- ngIf: app.formatter !== 'percent' -->
                                                    <div class="limits-data" ng-if="app.formatter !== 'percent'">
                                                        <span id="lblstats_bandwidth_count">7&nbsp;KB</span>
                                                        <!-- ngIf: ::app.formattedMaximum --><span ng-if="::app.formattedMaximum">
                                                        /
                                                        <span id="lblstats_bandwidth">8</span>
                                                        </span><!-- end ngIf: ::app.formattedMaximum -->
                                                        <!-- ngIf: ::app.showPercent -->
                                                    </div>
                                                    <!-- end ngIf: app.formatter !== 'percent' -->
                                                </div>
                                                <!-- end limits-wrapper -->
                                                <!-- ngIf: ::app.showPercent -->
                                            </div>
                                            <!-- end ngIf: !app.error -->
                                            <!-- ngIf: app.error -->
                                        </td>
                                        <td class="app-stat-upgrade">
                                            <!-- ngIf: ::app.needFix -->
                                        </td>
                                    </tr>
                                    <!-- end ngRepeat: app in ::statistics | orderBy:'-percent' track by app.id -->
                                    <tr id="row_subdomains" ng-repeat="app in ::statistics | orderBy:'-percent' track by app.id" ng-class="::getStatStatus(app.percent, app.error)" class="app-stat-row success" style="">
                                        <td class="app-stat-data">
                                            <!-- ngIf: ::app.url --><a ng-if="::app.url" id="lnkstats_subdomains" class="app-name" href="subdomain/index.html">Subdomains</a><!-- end ngIf: ::app.url -->
                                            <!-- ngIf: ::!app.url -->
                                            <!-- ngIf: !app.error -->
                                            <div ng-if="!app.error">
                                                <!-- ngIf: app.formatter === 'percent' -->
                                                <div class="limits-wrapper">
                                                    <!-- ngIf: app.formatter !== 'percent' -->
                                                    <div class="limits-data" ng-if="app.formatter !== 'percent'">
                                                        <span id="lblstats_subdomains_count">0</span>
                                                        <!-- ngIf: ::app.formattedMaximum --><span ng-if="::app.formattedMaximum">
                                                        /
                                                        <span id="lblstats_subdomains">8</span>
                                                        </span><!-- end ngIf: ::app.formattedMaximum -->
                                                        <!-- ngIf: ::app.showPercent -->
                                                    </div>
                                                    <!-- end ngIf: app.formatter !== 'percent' -->
                                                </div>
                                                <!-- end limits-wrapper -->
                                                <!-- ngIf: ::app.showPercent -->
                                            </div>
                                            <!-- end ngIf: !app.error -->
                                            <!-- ngIf: app.error -->
                                        </td>
                                        <td class="app-stat-upgrade">
                                            <!-- ngIf: ::app.needFix -->
                                        </td>
                                    </tr>
                                    <!-- end ngRepeat: app in ::statistics | orderBy:'-percent' track by app.id -->
                                    <tr id="row_email_accounts" ng-repeat="app in ::statistics | orderBy:'-percent' track by app.id" ng-class="::getStatStatus(app.percent, app.error)" class="app-stat-row success" style="">
                                        <td class="app-stat-data">
                                            <!-- ngIf: ::app.url --><a ng-if="::app.url" id="lnkstats_email_accounts" class="app-name" href="email_accounts/index.html">Email Accounts</a><!-- end ngIf: ::app.url -->
                                            <!-- ngIf: ::!app.url -->
                                            <!-- ngIf: !app.error -->
                                            <div ng-if="!app.error">
                                                <!-- ngIf: app.formatter === 'percent' -->
                                                <div class="limits-wrapper">
                                                    <!-- ngIf: app.formatter !== 'percent' -->
                                                    <div class="limits-data" ng-if="app.formatter !== 'percent'">
                                                        <span id="lblstats_email_accounts_count">0</span>
                                                        <!-- ngIf: ::app.formattedMaximum --><span ng-if="::app.formattedMaximum">
                                                        /
                                                        <span id="lblstats_email_accounts">8</span>
                                                        </span><!-- end ngIf: ::app.formattedMaximum -->
                                                        <!-- ngIf: ::app.showPercent -->
                                                    </div>
                                                    <!-- end ngIf: app.formatter !== 'percent' -->
                                                </div>
                                                <!-- end limits-wrapper -->
                                                <!-- ngIf: ::app.showPercent -->
                                            </div>
                                            <!-- end ngIf: !app.error -->
                                            <!-- ngIf: app.error -->
                                        </td>
                                        <td class="app-stat-upgrade">
                                            <!-- ngIf: ::app.needFix -->
                                        </td>
                                    </tr>
                                    <!-- end ngRepeat: app in ::statistics | orderBy:'-percent' track by app.id -->
                                    <tr id="row_mailing_lists" ng-repeat="app in ::statistics | orderBy:'-percent' track by app.id" ng-class="::getStatStatus(app.percent, app.error)" class="app-stat-row success" style="">
                                        <td class="app-stat-data">
                                            <!-- ngIf: ::app.url --><a ng-if="::app.url" id="lnkstats_mailing_lists" class="app-name" href="mail/lists.html">Mailing Lists</a><!-- end ngIf: ::app.url -->
                                            <!-- ngIf: ::!app.url -->
                                            <!-- ngIf: !app.error -->
                                            <div ng-if="!app.error">
                                                <!-- ngIf: app.formatter === 'percent' -->
                                                <div class="limits-wrapper">
                                                    <!-- ngIf: app.formatter !== 'percent' -->
                                                    <div class="limits-data" ng-if="app.formatter !== 'percent'">
                                                        <span id="lblstats_mailing_lists_count">0</span>
                                                        <!-- ngIf: ::app.formattedMaximum --><span ng-if="::app.formattedMaximum">
                                                        /
                                                        <span id="lblstats_mailing_lists">8</span>
                                                        </span><!-- end ngIf: ::app.formattedMaximum -->
                                                        <!-- ngIf: ::app.showPercent -->
                                                    </div>
                                                    <!-- end ngIf: app.formatter !== 'percent' -->
                                                </div>
                                                <!-- end limits-wrapper -->
                                                <!-- ngIf: ::app.showPercent -->
                                            </div>
                                            <!-- end ngIf: !app.error -->
                                            <!-- ngIf: app.error -->
                                        </td>
                                        <td class="app-stat-upgrade">
                                            <!-- ngIf: ::app.needFix -->
                                        </td>
                                    </tr>
                                    <!-- end ngRepeat: app in ::statistics | orderBy:'-percent' track by app.id -->
                                    <tr id="row_autoresponders" ng-repeat="app in ::statistics | orderBy:'-percent' track by app.id" ng-class="::getStatStatus(app.percent, app.error)" class="app-stat-row success" style="">
                                        <td class="app-stat-data">
                                            <!-- ngIf: ::app.url --><a ng-if="::app.url" id="lnkstats_autoresponders" class="app-name" href="mail/autores.html">Autoresponders</a><!-- end ngIf: ::app.url -->
                                            <!-- ngIf: ::!app.url -->
                                            <!-- ngIf: !app.error -->
                                            <div ng-if="!app.error">
                                                <!-- ngIf: app.formatter === 'percent' -->
                                                <div class="limits-wrapper">
                                                    <!-- ngIf: app.formatter !== 'percent' -->
                                                    <div class="limits-data" ng-if="app.formatter !== 'percent'">
                                                        <span id="lblstats_autoresponders_count">0</span>
                                                        <!-- ngIf: ::app.formattedMaximum --><span ng-if="::app.formattedMaximum">
                                                        /
                                                        <span id="lblstats_autoresponders">8</span>
                                                        </span><!-- end ngIf: ::app.formattedMaximum -->
                                                        <!-- ngIf: ::app.showPercent -->
                                                    </div>
                                                    <!-- end ngIf: app.formatter !== 'percent' -->
                                                </div>
                                                <!-- end limits-wrapper -->
                                                <!-- ngIf: ::app.showPercent -->
                                            </div>
                                            <!-- end ngIf: !app.error -->
                                            <!-- ngIf: app.error -->
                                        </td>
                                        <td class="app-stat-upgrade">
                                            <!-- ngIf: ::app.needFix -->
                                        </td>
                                    </tr>
                                    <!-- end ngRepeat: app in ::statistics | orderBy:'-percent' track by app.id -->
                                    <tr id="row_forwarders" ng-repeat="app in ::statistics | orderBy:'-percent' track by app.id" ng-class="::getStatStatus(app.percent, app.error)" class="app-stat-row success" style="">
                                        <td class="app-stat-data">
                                            <!-- ngIf: ::app.url --><a ng-if="::app.url" id="lnkstats_forwarders" class="app-name" href="mail/fwds.html">Forwarders</a><!-- end ngIf: ::app.url -->
                                            <!-- ngIf: ::!app.url -->
                                            <!-- ngIf: !app.error -->
                                            <div ng-if="!app.error">
                                                <!-- ngIf: app.formatter === 'percent' -->
                                                <div class="limits-wrapper">
                                                    <!-- ngIf: app.formatter !== 'percent' -->
                                                    <div class="limits-data" ng-if="app.formatter !== 'percent'">
                                                        <span id="lblstats_forwarders_count">0</span>
                                                        <!-- ngIf: ::app.formattedMaximum --><span ng-if="::app.formattedMaximum">
                                                        /
                                                        <span id="lblstats_forwarders">8</span>
                                                        </span><!-- end ngIf: ::app.formattedMaximum -->
                                                        <!-- ngIf: ::app.showPercent -->
                                                    </div>
                                                    <!-- end ngIf: app.formatter !== 'percent' -->
                                                </div>
                                                <!-- end limits-wrapper -->
                                                <!-- ngIf: ::app.showPercent -->
                                            </div>
                                            <!-- end ngIf: !app.error -->
                                            <!-- ngIf: app.error -->
                                        </td>
                                        <td class="app-stat-upgrade">
                                            <!-- ngIf: ::app.needFix -->
                                        </td>
                                    </tr>
                                    <!-- end ngRepeat: app in ::statistics | orderBy:'-percent' track by app.id -->
                                    <tr id="row_email_filters" ng-repeat="app in ::statistics | orderBy:'-percent' track by app.id" ng-class="::getStatStatus(app.percent, app.error)" class="app-stat-row success" style="">
                                        <td class="app-stat-data">
                                            <!-- ngIf: ::app.url --><a ng-if="::app.url" id="lnkstats_email_filters" class="app-name" href="mail/filters/managefilters.html">Email Filters</a><!-- end ngIf: ::app.url -->
                                            <!-- ngIf: ::!app.url -->
                                            <!-- ngIf: !app.error -->
                                            <div ng-if="!app.error">
                                                <!-- ngIf: app.formatter === 'percent' -->
                                                <div class="limits-wrapper">
                                                    <!-- ngIf: app.formatter !== 'percent' -->
                                                    <div class="limits-data" ng-if="app.formatter !== 'percent'">
                                                        <span id="lblstats_email_filters_count">0</span>
                                                        <!-- ngIf: ::app.formattedMaximum --><span ng-if="::app.formattedMaximum">
                                                        /
                                                        <span id="lblstats_email_filters">8</span>
                                                        </span><!-- end ngIf: ::app.formattedMaximum -->
                                                        <!-- ngIf: ::app.showPercent -->
                                                    </div>
                                                    <!-- end ngIf: app.formatter !== 'percent' -->
                                                </div>
                                                <!-- end limits-wrapper -->
                                                <!-- ngIf: ::app.showPercent -->
                                            </div>
                                            <!-- end ngIf: !app.error -->
                                            <!-- ngIf: app.error -->
                                        </td>
                                        <td class="app-stat-upgrade">
                                            <!-- ngIf: ::app.needFix -->
                                        </td>
                                    </tr>
                                    <!-- end ngRepeat: app in ::statistics | orderBy:'-percent' track by app.id -->
                                    <tr id="row_mysql_databases" ng-repeat="app in ::statistics | orderBy:'-percent' track by app.id" ng-class="::getStatStatus(app.percent, app.error)" class="app-stat-row success" style="">
                                        <td class="app-stat-data">
                                            <!-- ngIf: ::app.url --><a ng-if="::app.url" id="lnkstats_mysql_databases" class="app-name" href="sql/index.html">MySQL� Databases</a><!-- end ngIf: ::app.url -->
                                            <!-- ngIf: ::!app.url -->
                                            <!-- ngIf: !app.error -->
                                            <div ng-if="!app.error">
                                                <!-- ngIf: app.formatter === 'percent' -->
                                                <div class="limits-wrapper">
                                                    <!-- ngIf: app.formatter !== 'percent' -->
                                                    <div class="limits-data" ng-if="app.formatter !== 'percent'">
                                                        <span id="lblstats_mysql_databases_count">0</span>
                                                        <!-- ngIf: ::app.formattedMaximum --><span ng-if="::app.formattedMaximum">
                                                        /
                                                        <span id="lblstats_mysql_databases">8</span>
                                                        </span><!-- end ngIf: ::app.formattedMaximum -->
                                                        <!-- ngIf: ::app.showPercent -->
                                                    </div>
                                                    <!-- end ngIf: app.formatter !== 'percent' -->
                                                </div>
                                                <!-- end limits-wrapper -->
                                                <!-- ngIf: ::app.showPercent -->
                                            </div>
                                            <!-- end ngIf: !app.error -->
                                            <!-- ngIf: app.error -->
                                        </td>
                                        <td class="app-stat-upgrade">
                                            <!-- ngIf: ::app.needFix -->
                                        </td>
                                    </tr>
                                    <!-- end ngRepeat: app in ::statistics | orderBy:'-percent' track by app.id -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- end stats -->
                </div>
            </div>
            <!-- PAGE TEMPLATE'S CONTENT END -->
        </div>
        <footer role="contentinfo">




        </footer>
     </body>
</html>

<?php }}); ?>


<?php $router->get('dsd', function() { ?>

<?php }); ?>


<?php $router->get('phpinfo', function() { ?>

<?php echo phpinfo();?>

<?php }) ?>


<?php $router->dispatchGlobal(); ?>