<?php
/*
Copyright 2014 Woody Goodricke

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

	http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
*/

namespace Restme;

class Http {
	private $endpoints = array();
	private $route_params = array();
	private $error = array();
	private $_responses = array(
		100 => 'Continue',
		101 => 'Switching Protocols',
		102 => 'Processing',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		207 => 'Multi-Status',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		306 => 'Switch Proxy',
		307 => 'Temporary Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		418 => 'I\'m a teapot',
		422 => 'Unprocessable Entity',
		423 => 'Locked',
		424 => 'Failed Dependency',
		425 => 'Unordered Collection',
		426 => 'Upgrade Required',
		449 => 'Retry With',
		450 => 'Blocked by Windows Parental Controls',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		506 => 'Variant Also Negotiates',
		507 => 'Insufficient Storage',
		509 => 'Bandwidth Limit Exceeded',
		510 => 'Not Extended'
	);

	// Add all the variables used in the class.

	/**
	 * Sets the variables up for the request made
	 *
	 * @return Void
	 */
	public function __construct () {
		// Split out endpoint class
		$la_script_path = explode('/', $_SERVER['SCRIPT_NAME']);
		$la_script_name = array_pop($la_script_path);
		//
		$la_script_name = explode('.', $la_script_name);
		array_pop($la_script_name);
		$ls_script_name = ucfirst(implode('.', $la_script_name));

		$ls_endpoint_file_path = './endpoint/' . $ls_script_name . '.php';
		if (!file_exists($ls_endpoint_file_path)) {
			$this->set_http_response(500);
			die(json_encode(array('error' => array("Cannot find corresponding endpoint file: $ls_endpoint_file_path")))); // Convert to json 
		}
		include $ls_endpoint_file_path;

		$ls_endpoint_name = 'RestmeEndpoint\\' . $ls_script_name;
		$this->endpoint_class = new $ls_endpoint_name;

		$this->is_json = (array_key_exists('HTTP_ACCEPT', $_SERVER) && strpos($_SERVER['HTTP_ACCEPT'], 'json') !== false);
		$this->method = strtoupper($_SERVER['REQUEST_METHOD']);
		$this->true_method = $this->_get_true_method();
		$this->version = (!empty($_SERVER['ACCEPT_VERSION']) || !empty($_SERVER['HTTP_ACCEPT_VERSION'])) ? $_SERVER['ACCEPT_VERSION'] . $_SERVER['HTTP_ACCEPT_VERSION'] : '0.0.1';

		$this->query = $this->get_query();

		$li_path_length = strlen(implode('/', $la_script_path));
		$la_request_uri = parse_url(substr($_SERVER['REQUEST_URI'], $li_path_length)); // get rid of the path up to the file.
		$la_request_uri = explode('/', trim($la_request_uri['path'], '/'));
		
		// $this->url_parts = new RURL_mapper($la_request_uri);
		$this->url_parts = $la_request_uri;
	}

	/**
	 * Takes a response code and sets the correct header
	 *
	 * @access protected
	 *
	 * @param  integer $pi_response_code    HTTP response code
	 * 
	 * @return void
	 */
	protected function set_http_response ($pi_response_code) {
		$ls_response_text = $this->_responses[$pi_response_code];
		// Set response as header
		header("HTTP/1.1 $pi_response_code $ls_response_text");
	}

	/**
	 * Checks for method override and returns the it if found, otherwise returns the method type that
	 * was originally retrieved. This allows for PUT/DELETE requests even though it's not truly supported
	 * on our web servers.
	 *
	 * @access protected
	 * @return	string	The true method type (GET/POST/PUT/DELETE)
	 */
	protected function _get_true_method () {
		$la_possible_keys_for_method_retrieval = array(
			'HTTP_X_HTTP_METHOD_OVERRIDE',
			'X_HTTP_METHOD_OVERRIDE',
			'HTTP_METHOD_OVERRIDE'
		);

		// By comparing the list of possible override keys with the keys present in the server superglobal, the correct
		// override key can be retrieved.
		$la_method_override = array_intersect($la_possible_keys_for_method_retrieval, array_keys($_SERVER));

		// If an override method was found, return that value.
		if (count($la_method_override)) {
			reset($la_method_override);
			$li_key_found = key($la_method_override);
			return $_SERVER[$la_method_override[$li_key_found]];
		};

		// There is no override, return the original method.
		return $this->method;
	}

	/**
	 * Gets the appropriate query according to the method called
	 *
	 * @return [array]
	 */
	protected function get_query () {
		$request_body = @file_get_contents('php://input', 'r');
		// f_debug('request_body', $request_body);
		if (!empty($request_body)) {
			$lm_params = json_decode($request_body);
			if (empty($lm_params)) {
				parse_str($request_body, $lm_params);
			}
		}

		if (!empty($lm_params)) {
			return $lm_params;
		}
		switch ($this->method) {
			case 'GET':
				return $_GET;
				break;

			case 'POST':
				return $_POST;
				break;

			case 'DELETE':
			case 'PUT':
					return $_POST;
				break;

			default:
				return $_REQUEST;
				break;
		}
	}

	/**
	 * Adds an error to the stack for if there's an error.
	 *
	 * @return Void
	 */
	protected function add_error ($ps_error_message) {
		array_push($this->error, $ps_error_message);
	}

	/**
	 * locates the endpoint given the current path called.
	 *
	 * @return [string]                   endpoint
	 */
	protected function get_endpoint_from_route () {
		$this->add_error("Start!");
		foreach ($this->endpoints[$this->true_method][$this->version] as $ls_route_pattern => $ls_endpoint) {
			
			$la_url_parts = $this->url_parts;
			$this->add_error(array("la_url_parts" => $la_url_parts));
			$this->add_error(array("ls_endpoint" => $ls_endpoint));
			$la_return_params = array();
			
			$la_route_pattern = explode('/', $ls_route_pattern);
			// Test for array of route parameters
			if (is_array($la_route_pattern)) {
				$this->add_error(array("la_route_pattern" => $la_route_pattern));
				if (count($la_route_pattern) === count($la_url_parts)) {
					// Loop through each of the required route segments
					foreach ($la_route_pattern as $ls_segment) {
						$this->add_error(array("ls_segment" => $ls_segment));
						// If url parts is a populated array
						if (is_array($la_url_parts) && count($la_url_parts)) {
							// Shift the top url_part from the array
							$ls_param = array_shift($la_url_parts);
							$this->add_error(array("ls_param" => $ls_param));

							// Test to see if the segment is named parameter 
							if (preg_match('/^\:(.*)/', $ls_segment, $lm_segment_key)) {
								// Add that as a key/value pairing to the return params
								$la_return_params[$lm_segment_key[1]] = $ls_param;
							}
							elseif ($ls_segment !== $ls_param) {
								$this->add_error("Not matched!");
								// Not matched
								continue 2;
							}
						}
					}
					$this->add_error(array("la_return_params" => $la_return_params));
					$this->route_params = $la_return_params;

					return $ls_endpoint;
				}
				else {
					$this->add_error('Counts do not match');
					continue;
				}
			}
		}
		return false; //if not matched;
	}

	/**
	 * Sets up a http route
	 *
	 * @param  [string] $ps_method        type of http request (GET, POST, PUT, DELETE)
	 * @param  [mixed]  $pm_route         either string (route) or array (path, version)
	 * @param  [string] $ps_endpoint      method to run in the accompying class
	 *
	 * @return [boolean]                  the success of adding the route
	 */
	protected function add_http_route ($ps_method, $pm_route, $ps_endpoint) {
		
		if (empty($ps_method) || empty($pm_route) || (is_array($pm_route) && empty($pm_route['path'])) || empty($ps_endpoint)) {
			
			$this->add_error(array(
				"Could not add route; parameter empty" => array(
					'ps_method' => $ps_method
				  , 'pm_route' => $pm_route
				  , 'ps_endpoint' => $ps_endpoint
				)
			));
			return false;
		}

		if (is_array($pm_route)) {
			// shouldn't need to check this but we love safety.
			if (array_key_exists('path', $pm_route)) {
				$ls_route = $pm_route['path'];
			}
			else {
				// Boy is my face red - see previous comment.
				$this->add_error(array(
					"Could not add route; route path not passed" => array(
						'ps_method' => $ps_method
					  , 'pm_route' => $pm_route
					  , 'ps_endpoint' => $ps_endpoint
					)
				));
				return false;
			}

			if (array_key_exists('version', $pm_route)) {
				$ls_version = $pm_route['version'];
			}
		}
		else {
			$ls_route = $pm_route;
		}
		$ls_version = $ls_version ? $ls_version : '0.0.1'; //$_SERVER['Accept-Version']

		// Add method if not present.
		if (!array_key_exists($ps_method, $this->endpoints)) {
			$this->endpoints[$ps_method] = array();
		}
		// Add version if not present.
		if (!array_key_exists($ls_version, $this->endpoints[$ps_method])) {
			$this->endpoints[$ps_method][$ls_version] = array();
		}

		$this->endpoints[$ps_method][$ls_version][$ls_route] = $ps_endpoint;
		return true;
	}

	/**
	 * Sets up a route for GET
	 *
	 * @param  [mixed]  $pm_route         passed through, see @link add_http_route
	 * @param  [string] $ps_endpoint      passed through, see @link add_http_route
	 *
	 * @return [boolean]                  the success of adding the route
	 */
	function get ($pm_route, $ps_endpoint) {
		return $this->add_http_route('GET', $pm_route, $ps_endpoint);
	}

	/**
	 * Sets up a route for POST
	 *
	 * @param  [mixed]  $pm_route         passed through, see @link add_http_route
	 * @param  [string] $ps_endpoint      passed through, see @link add_http_route
	 *
	 * @return [boolean]                  the success of adding the route
	 */
	function post ($pm_route, $ps_endpoint) {
		return $this->add_http_route('POST', $pm_route, $ps_endpoint);
	}

	/**
	 * Sets up a route for PUT
	 *
	 * @param  [mixed]  $pm_route         passed through, see @link add_http_route
	 * @param  [string] $ps_endpoint      passed through, see @link add_http_route
	 *
	 * @return [boolean]                  the success of adding the route
	 */
	function put ($pm_route, $ps_endpoint) {
		return $this->add_http_route('PUT', $pm_route, $ps_endpoint);
	}

	/**
	 * Sets up a route for DELETE
	 *
	 * @param  [mixed]  $pm_route         passed through, see @link add_http_route
	 * @param  [string] $ps_endpoint      passed through, see @link add_http_route
	 *
	 * @return [boolean]                  the success of adding the route
	 */
	function delete ($pm_route, $ps_endpoint) {
		return $this->add_http_route('DELETE', $pm_route, $ps_endpoint);
	}

	/**
	 * Locates the applicable route,
	 * runs the endpoint
	 * outputs appropriate headers
	 * and json of result.
	 *
	 * @return Void
	 */
	function response () {
		$lb_endpoint_reached = true;
		$la_endpoint_result = array();
		// check method
		if (!array_key_exists($this->true_method, $this->endpoints)) {
			$lb_endpoint_reached = false; 
			
			$this->add_error("Method not added for: $this->true_method");
		}
		
		// check version (strict at the moment 0.1.0 must match 0.1.0)
		if ($lb_endpoint_reached && !array_key_exists($this->version, $this->endpoints[$this->true_method])) {
			$lb_endpoint_reached = false; 
			
			$this->add_error("Version not added for: $this->version");
		}

		// check pattern
		if ($lb_endpoint_reached) {
			// work out parameters (e.g. :id)
			$lm_endpoint = $this->get_endpoint_from_route();

			if ($lm_endpoint !== false) {
				if (method_exists($this->endpoint_class, $lm_endpoint)) {
					// call endpoint
					$la_endpoint_result = call_user_func(array($this->endpoint_class, $lm_endpoint), $this->route_params, $this->query);
				}
				else {
					$lb_endpoint_reached = false; 
					
					$this->add_error(array(
						"Endpoint not added" => $this
					));
				}


			}
			else {
				$lb_endpoint_reached = false; 
				
				$this->add_error(array(
					"Route not added" => $this
				  , "SERVER" => $_SERVER
				));
			}
		}
		header('Content-Type: application/json');
		// not matched:
		if (!$lb_endpoint_reached) {
			$this->set_http_response(404);
			echo json_encode($this->error);
			exit();
		}
		// check endpoint return (should be array)
		if (is_array($la_endpoint_result) && !empty($la_endpoint_result)) {
			// Check to see if a specific http response was returned
			if (array_key_exists('response', $la_endpoint_result) && array_key_exists('data', $la_endpoint_result) && array_key_exists($la_endpoint_result['response'], $this->_responses)) {
				$response_key = $la_endpoint_result['response'];
				$this->set_http_response($response_key);
				// Redefine endpoint result as data
				$la_endpoint_result = $la_endpoint_result['data'];
			}
			else {
				$this->set_http_response(200);
			}
			// echo json out
			echo json_encode($la_endpoint_result);
		}
	}
}
