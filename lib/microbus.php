<?php

if (!defined('VIEWS_PATH')) {
	define('VIEWS_PATH',dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR."views".DIRECTORY_SEPARATOR);
	define('LAYOUTS_PATH',VIEWS_PATH."layouts".DIRECTORY_SEPARATOR);
	
}

/**
 * View
 *
 * @package default
 * @author Armando Sosa
 */
class View{

	static private $_content;
	static $vars = array(
			'pageTitle' => 'Microbus',
		);

	static function render($view = ''){
		$view = VIEWS_PATH.$view.".php";			

		if (self::exists($view)) {
			self::start();
			extract(View::$vars);
			include	$view;
			return self::end();
		}
		
		return false;
	}
	
	static function start(){
		ob_start();		
	}
	
	static function end($withLayout = true){

		self::setContent(ob_get_contents());
		ob_end_clean();	

		if ($withLayout) {
			return self::renderlayout();						
		}
		
		return self::content();
	}
	
	function renderLayout($layout = 'default'){

		$layout = LAYOUTS_PATH.$layout.".php";

		if (self::exists($layout)) {
			extract(View::$vars);			
			include $layout;
			return true;
		}
		
		return false;
	}
	
	function setContent($content = ''){
		self::$_content = $content;
	}
	
	function content(){
		return self::$_content;
	}
	
	function output(){
		echo self::content();
	}
	
	static function exists($view){
		return file_exists($view);
	}
	
	function set($key,$value){
		self::$vars[$key] = $value;
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
	 * undocumented function
	 *
	 * @param string $app 
	 * @return void
	 * @author Armando Sosa
	 */
	static function setApp($app = null){
		if (!$app) {
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
	static function setRequest($request){
		if (empty(self::$request)) {
			self::$request = array(
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
	static function call(){
		
		$method = self::request('method');
		$params = self::request('params');
		
		if (is_object(self::$app) && method_exists(self::$app,$method)) {
			View::start();			
			call_user_func(array(self::$app,$method),$params);
			View::end();						
			return;
		}
		
		if (function_exists($method)) {
			View::start();
			call_user_func_array($method,$params);
			View::end();			
			return;
		}
		
		
		if (!View::render(self::request('action'))){
			echo '404';
		}

		
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
		
		// we'll just route the first time.
		if (in_array($route,self::$routed)) {
			return false;
		}
		
		list($verb,$path) = self::resolveRequest();	

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
				
		if ($match && is_callable($callback)) {

			self::$routed[] = $route;
			View::start();			
			call_user_func_array($callback,self::request('params'));
			View::end();			
			return true;
		}		
		
		return $match;
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
 * Application Class
 *
 * @package default
 * @author Armando Sosa
 */
if (!class_exists('Application')) {
	class Application{
		
	}
}


/*
	Functions
*/

function get($route, $callback = null){
	return Microbus::route('get',$route,$callback);
}

function post($route, $callback = null){
	return Microbus::route('post',$route,$callback);
}

function put($route, $callback = null){
	return Microbus::route('put',$route,$callback);
}

function delete($route, $callback = null){
	return Microbus::route('delete',$route,$callback);
}


function str_contains($pattern,$str){
	$p = strpos($str,$pattern);
	return ($p !== false);
}

function str_starts_with($pattern,$str){
	$p = strpos($str,$pattern);
	return ($p === 0);
}



function pr($var){
	echo "<pre>";
	print_r($var);
	echo "</pre>";
}

?>