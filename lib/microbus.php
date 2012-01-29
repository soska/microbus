<?php

if (!defined('DS')) define('DS',DIRECTORY_SEPARATOR);
if (!defined('ROOT_PATH')) define('ROOT_PATH',dirname(dirname(__FILE__)).DS);
if (!defined('VIEWS_PATH')) define('VIEWS_PATH',ROOT_PATH."views".DS);
if (!defined('LAYOUTS_PATH')) define('LAYOUTS_PATH',VIEWS_PATH."layouts".DS);
if (!defined('TMP_PATH')) define('TMP_PATH',ROOT_PATH."tmp".DS);


/**
 * View
 *
 * @package default
 * @author Armando Sosa
 */
class View{

	static private $_content;
	static private $_layout = 'default';
	static private $_extension = "php";
	static public $current = "";

	static $vars = array(
			'pageTitle' => 'Microbus',
		);

	static function render($view = '',$withLayout = true){
		self::$current = $view;
		$view = VIEWS_PATH.$view.".".self::$_extension;

		if (self::exists($view)) {
			self::start();
			if (Pimp::ed('include_view')) {
				Pimp::call('include_view',$view,View::$vars);
			}else{
				extract(View::$vars);
				include	$view;
			}
			return self::end($withLayout);
		}

		return false;
	}

	static function element($view = '',$withLayout = true){
		self::$current = $view;
		$view = VIEWS_PATH.$view.".".self::$_extension;

		if (self::exists($view)) {
				extract(View::$vars);
				include	$view;
		}

		return false;
	}


	static function start(){
		ob_start();
	}

	static function end($withLayout = true){
		$content = ob_get_contents();
		$content = Pimp::filter('filter_content',$content);
		self::setContent($content);
		ob_end_clean();

		if ($withLayout) {
			return self::renderLayout();
		}

		return self::content();
	}

	function renderLayout($layout = null){

		if (function_exists('Layout')) {
			return Layout();
		}

		if(!$layout){
			$layout = self::$_layout;
		}

		$layout = LAYOUTS_PATH.$layout.".".self::$_extension;


		if (self::exists($layout)) {
			extract(View::$vars);
			include $layout;
			return true;
		}

		return false;
	}

	function setLayout($layout = ''){
		self::$_layout = $layout;
	}

	function setFileExtension($ext = false){
		if ($ext) {
			self::$_extension = $ext;
		}
	}

	function setContent($content = '', $append = false){
		if ($append) {
			self::$_content .= $content;
		}else{
			self::$_content = $content;
		}
	}

	function content(){
		return self::$_content;
	}

	function output($string){
		self::setContent($string,true);
	}

	function yield(){
		echo self::content();
	}

	static function exists($view){
		return file_exists($view);
	}

	function set($key,$value = null){
		if (is_array($key)) {
			foreach ($key as $k => $v) {
				self::$vars[$k] = $v;
			}
		}else{
			self::$vars[$key] = $value;
		}

	}

	function get($key){
		if (isset(self::$vars[$key])) {
			return self::$vars[$key];
		}
		return false;
	}
}



/**
 * Microbus
 *
 * @package default
 * @author Armando Sosa
 */
class Microbus{

	/**
	 * undocumented variable
	 *
	 * @var string
	 */
	static private $request;

	/**
	 * undocumented variable
	 *
	 * @var string
	 */
	static public $app;

	/**
	 * undocumented variable
	 *
	 * @var string
	 */
	static public $routed = array();

	/**
	 * route Prefix
	 *
	 * @var string
	 */
	static public $routePrefix = '';
	/**
	 * undocumented function
	 *
	 * @param string $app
	 * @return void
	 * @author Armando Sosa
	 */
	static function ride($app = null){
		self::setApp($app);
		self::dispatch();
	}

	/**
	 * redirects to a certain url
	 *
	 * @param string $url
	 * @return void
	 * @author Armando Sosa
	 */
	static function redirect($url = null){

		if (!$url) {
			$url = self::request('path');
		}

		if (!str_starts_with('http://',$url)) {

			if ($url == '/') {
				$url = self::request('baseUrl');
			}elseif (str_starts_with('/',$url)) {
				$url = self::request('baseUrl').substr($url,1);
			}else{
				$url = self::request('baseUrl').self::request('action').'/'.$url;
			}

		}


		header('Location:'.$url);
		exit;
	}

	/**
	 * undocumented function
	 *
	 * @param string $app
	 * @return void
	 * @author Armando Sosa
	 */
	static function setApp($app = null){
		if (!$app && class_exists('Application')) {
			$app = new Application;
		}
		self::$app = $app;
	}

	/**
	 * undocumented function
	 *
	 * @param string $request
	 * @return void
	 * @author Armando Sosa
	 */
	static function setRequest($key,$value = false){

		if (is_array($key)) {
			$request = $key;
		}else{
			$request = array($key=>$value);
		}

		if (empty(self::$request)) {
			self::$request = array(
					'subdomain'=>false,
					'route'=>'',
					'verb'=>'get',
					'path'=>'/',
					'action'=>'index',
					'params'=>array(),
					'data'=>array(),
					'named'=>array(),
					'method'=>null,
				);
		}
		self::$request = array_merge(self::$request,$request);
	}

	/**
	 * undocumented function
	 *
	 * @param string $key
	 * @return void
	 * @author Armando Sosa
	 */
	static function request($key = ''){
		if (empty($key)) {
			return self::$request;
		}
		if (isset(self::$request[$key])) {
			return self::$request[$key];
		}
		return false;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Armando Sosa
	 */
	function resolveRequest(){
		$verb = strtolower($_SERVER['REQUEST_METHOD']);
		$path = isset($_SERVER['PATH_INFO'])?$_SERVER['PATH_INFO']:'';
		return array($verb,$path);
	}

	/**
	 * If we are on a subdmain, we'll get it.
	 *
	 * @return void
	 * @author Armando Sosa
	 */
	function resolveSubdomain($domain,$callback = false){

		$subdomain = false;

		$host = $_SERVER['HTTP_HOST'];
		$sub = str_replace($domain,'',$host);
		$parts = explode('.',$sub);

		if (is_array($parts) && !empty($parts[0])) {
			$subdomain = $parts[0];
		}

		self::setRequest('subdomain',$subdomain);

		if (is_callable($callback)) {
			return call_user_func($callback,$subdomain);
		}

		return $subdomain;

	}

	/**
	 * Sets a base URL, not sure that this works in every case.
	 *
	 * @param string $base
	 * @return void
	 * @author Armando Sosa
	 */
	function setBaseUrl($base = null){

		if ($base) {
			str_replace('http://','',$base);
		}else{
			$request = self::request();

			$base = $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
			$base = str_replace($request['path'],'',$base);
			if (!empty($request['subdomain'])) {
				$base = $request['subdomain'].".".$base;
			}
		}

		self::setRequest('baseUrl',"http://".$base);
	}

	/**
	 * This method is used to dispatch automagic verb+function/method/view
	 *
	 * @return void
	 * @author Armando Sosa
	 */
	function dispatch(){
		// we'll dispatch just the first time.
		list($verb,$path) = self::resolveRequest();

		if (empty($path)){
			$path = '/';
			$params = false;
		}

		$route = $path;
		if (in_array($route,self::$routed)) {
			return false;
		}else{
			self::$routed[] = $route;
		}

		if ($path != '/') {
			$path = preg_replace('/^\/|\/$/','',$path);
			$params = explode('/',$path);
			$action = array_shift(&$params);
		}else{
			$params = array();
			$action = 'index';
		}

		$data = self::getData($verb);

		$method = self::getMethodName($verb, $action);

		self::setRequest(compact('verb','path','action','params','data','method'));

		self::setBaseUrl();
		self::call();
	}

	/**
	 * Assing either $_POST, $_DATA, etc. depending on the verb
	 *
	 * @param string $verb
	 * @return void
	 * @author Armando Sosa
	 */
	function getData($verb){
		switch ($verb) {
			case 'get':
				$data = $_GET;
				break;

			case 'post':
				$data = $_POST;
				break;
		}
		return $data;
	}

	/**
	 * Generates an automagic method name.
	 *
	 * @param string $verb either 'get','post','put' or 'delete'
	 * @param string $action
	 * @return void
	 * @author Armando Sosa
	 */
	function getMethodName($verb,$action){
		return $verb."_".$action;
	}


	/**
	 * Call's automagic stuff in this order.
	 * 1. A method in self::$app if that method is declared
	 * 2. A global function if that function exists.
	 * 3. Will just render a view if nothing else matches.
	 *
	 * @return void
	 * @author Armando Sosa
	 */
	static function call($method = null, $params = null, $recursion = false){

		if (!$method) {
			$method = self::request('method');
		}
		if (!$params) {
			$params = self::request('params');
		}

		if (is_object(self::$app) && method_exists(self::$app,$method)) {
			call_user_func(array(self::$app,$method),$params);
			return;
		}

		if (function_exists($method)) {
			call_user_func_array($method,$params);
			return;
		}

		if (!View::render(self::request('action'))){
			if (!$recursion) {
				self::call('not_found',array(),true);
			}else{
				header("HTTP/1.0 404 Not Found");
				die('Error 404');
			}
		}


	}


	function setRoutePrefix($prefix = ''){
		self::$routePrefix = $prefix;
	}

	/**
	 * undocumented function
	 *
	 * @param string $routeVerb
	 * @param string $route
	 * @param string $callback
	 * @return void
	 * @author Armando Sosa
	 */
	static function route($routeVerb,$route,$callback = null){

		self::setApp();

		// we'll just route the first time.
		if (in_array($route,self::$routed)) {
			return false;
		}

		list($verb,$path) = self::resolveRequest();

		// not the actual http method
		if ($routeVerb != $verb) {
			return false;
		}

		// no path, defaults to root
		if (empty($path)) {
			$path = "/";
		}

		// check if the route matches the current path.
		$match = self::routeMatch($route, $path);

		self::setRequest(array('verb'=>$verb));

		if ($match) {
			// check that this route is only executed once.
			if (in_array(self::request('route'),self::$routed)) {
				return false;
			}else{
				self::$routed[] = self::request('route');
			}
		}

		self::setRequest(array('data'=>self::getData($verb)));

		if (is_callable(array(self::$app,$callback))) {
			$callback = array(self::$app,$callback);
		}

		if ($match && is_callable($callback)) {
			self::$routed[] = $route;
			call_user_func_array($callback,self::request('params'));
			return true;
		}

		return $match;
	}

	function pass(){
		array_pop(self::$routed);
	}

	/**
	 * matches a given route against the current path
	 *
	 * @param string $route
	 * @param string $path
	 * @return void
	 * @author Armando Sosa
	 */
	static function routeMatch($route,$path){

		// the route minus the prefix
		$path = str_replace(self::$routePrefix,'',$path);

		if ($route == $path) {
			self::setRequest(array('route'=>$path));
			return true;
		}

		$match = false;
		$r = explode('/',preg_replace('/^\/|\/$/','',$route));
		$p = explode('/',preg_replace('/^\/|\/$/','',$path));
		$n = max(count($r),count($p));
		$root = '';
		$params = array();
		$named = array();
		$wildCardMode = false;


		for ($i=0; $i < $n; $i++) {


			// route is longer than path. no deal
			if (!isset($p[$i])) {
				if (!$wildCardMode) {
					$match = false;
				}
				break;
			}

			// path longer than route. Just fill it with an empty string. Hopefully we are in wildcardmode already.
			if (!isset($r[$i])) {
				$r[$i] = '';
			}

			// we sum the parts that match.
			if ($r[$i] == $p[$i]) {
				$root .= $p[$i]."/";
				$match = true;
			}else{
			// and evaluate the parts that don't.
				$match = false;

				// this route part is an asterisk, we'll enter in wildCardMode
				if (str_contains('*',$r[$i])) {
					$wildCardMode = true;
				}
				// when in wildCardMode just addd every path piece to the params array
				if ($wildCardMode) {
					if (!empty($p[$i])) {
						$params[] = $p[$i];
						$match = true;
					}
				// if we are not in wildcard mode , check if this a named parameter. If it is, just add it to the $named array.
				}elseif (str_starts_with(':',$r[$i])) {
					$key = str_replace(':','',$r[$i]);
					$named[$key] = $p[$i];
					$match = true;
				}
			}

			// something didn't matched. Get outta the loop.
			if (!$match) {
				break;
			}
		}


		if ($match) {
			self::setRequest(array('route'=>$root,'params'=>$params,'named'=>$named));
		}

		return $match;
	}
}


/**
 * Session Class
 *
 * @package default
 * @author Armando Sosa
 */

class Session{

	static private $started = false;

	function start(){
		if (!self::$started) {
			@session_start();
			self::$started = true;
		}
	}

	function set($key ='microbus', $value = 0){
		self::start();
		$value = serialize($value);
		$_SESSION[$key] = $value;
		return $_SESSION[$key];
	}

	function push($key, $value){
		self::start();
		$array = self::get($key);
		if (!is_array($array)) {
			return false;
		}
		$array[] = $value;
		return self::set($key,$array);
	}

	function get($key){
		self::start();
		if (!isset($_SESSION[$key])) {
			return false;
		}else{
			return unserialize($_SESSION[$key]);
		}
	}

	function delete($key){
		self::start();
		if (!isset($_SESSION[$key])) {
			return false;
		}else{
			unset($_SESSION[$key]);
		}
	}

}

/**
 * Pimp, plugin interface
 *
 * @package default
 * @author Armando Sosa
 */
class Pimp{

	static private $hooks = array();

	function hook($when,$do){
		if (is_callable($do)) {
			if (! isset(self::$hooks[$when])) {
				self::$hooks[$when] = array();
			}
			self::$hooks[$when] = $do;
		}
	}

	function ed($when){
		return (isset(self::$hooks[$when]));
	}

	function call(){
		$args = func_get_args();
		$when = array_shift($args);
		if (isset(self::$hooks[$when])) {
			foreach (self::$hooks as $hook) {
				if (is_callable($hook)) {
					call_user_func_array($hook,$args);
				}
			}
		}

	}

	function filter($when, $string){
		if (isset(self::$hooks[$when])) {
			foreach (self::$hooks[$when] as $hook) {
				if (is_callable($hook)) {
					$string = call_user_func($hook,$string);
				}
			}
		}

		return $string;

	}

}

/*
	Functions
*/


/**
 * get
 *
 * @param string $route
 * @param string $callback
 * @return void
 * @author Armando Sosa
 */
function get($route, $callback = null){
	return Microbus::route('get',$route,$callback);
}

/**
 * post
 *
 * @param string $route
 * @param string $callback
 * @return void
 * @author Armando Sosa
 */
function post($route, $callback = null){
	return Microbus::route('post',$route,$callback);
}

/**
 * put
 *
 * @param string $route
 * @param string $callback
 * @return void
 * @author Armando Sosa
 */
function put($route, $callback = null){
	return Microbus::route('put',$route,$callback);
}

/**
 * delete
 *
 * @param string $route
 * @param string $callback
 * @return void
 * @author Armando Sosa
 */
function delete($route, $callback = null){
	return Microbus::route('delete',$route,$callback);
}

/**
 * Resolves a subdomain
 *
 * @param string $domain
 * @param string $callback
 * @return void
 * @author Armando Sosa
 */
function subdomain_of($domain, $callback = false){
	return Microbus::resolveSubdomain($domain,$callback);
}



if (!function_exists('not_found')) {
	/**
	 * not_found
	 *
	 * @return void
	 * @author Armando Sosa
	 */
	function not_found(){
		header("HTTP/1.0 404 Not Found");
		View::output('<h2>Sorry, there\'s nothing here</h2>');
		View::set('pageTitle','404 not found');
		View::renderLayout();
	}
}

function flash($msg = null){
	if (empty($msg)) {
		$msg =  Session::get('flash');
		Session::delete('flash');
		return $msg;
	}else{
		Session::set('flash',$msg);
	}
}


/**
 * str_contains - return true if $str contains substring $pattern
 *
 * @param string $pattern
 * @param string $str
 * @return void
 * @author Armando Sosa
 */
function str_contains($pattern,$str){
	$p = strpos($str,$pattern);
	return ($p !== false);
}

/**
 * str_starts_with - returns tru if $pattern is contained at the beggining of $str
 *
 * @param string $pattern
 * @param string $str
 * @return void
 * @author Armando Sosa
 */
function str_starts_with($pattern,$str){
	$p = strpos($str,$pattern);
	return ($p === 0);
}

/**
 * pr - utility function for debuggin purposes
 *
 * @param string $var
 * @return void
 * @author Armando Sosa
 */
function pr($var){
	echo "<pre>";
	print_r($var);
	echo "</pre>";
}

?>