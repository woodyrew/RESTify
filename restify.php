<?php

include 'rurl_mapper.php';

define('ENDPOINT_DIR', dirname(__FILE__) . '/endpoints');

abstract class Restify {

	abstract public function onGet($data);
	abstract public function onPost($data);
	abstract public function onPut($data);
	abstract public function onDelete($data);

	public function __construct(){

		$this->method = strtoupper($_SERVER['REQUEST_METHOD']);

		$this->query = $this->get_query();

		$request_uri = parse_url($_SERVER['REQUEST_URI']);

		$request_uri = explode('/', trim($request_uri['path'], '/'));

		$this->rurlm = new RURL_mapper($request_uri);

	}

	public function doAction(){

		switch($this->method){
			case 'POST':
				return $this->onPost($this->query);
			break;
			case 'PUT':
				return $this->onPut($this->query);
			break;
			case 'DELETE':
				return $this->onDelete($this->query);
			break;
			case 'GET':
			default:
				return $this->onGet($this->query);
			break;
		}
	}

	protected function get_query(){
    switch($this->method){
      case 'GET':
        return $_GET;
      break;
      case 'POST':
        return $_POST;
      break;
      case 'DELETE':
      case 'PUT':
        $request_body = @file_get_contents('php://input', 'r');
        parse_str($request_body, $_PUT);
        return $_PUT;
      break;
      default:
        return $_REQUEST;
      break;
		}
	}

	protected function rurl_mapper(){
		return $this->rurlm;
	}

	static public function endpoint($EP_Class){
		$EP_File = ENDPOINT_DIR . '/' . $EP_Class . '.php';
		if(! file_exists($EP_File)) die('Cannot find EndPoint class');
		include $EP_File;
		$EP_object = new $EP_Class;
		return $EP_object->doAction();
	}

}

?>