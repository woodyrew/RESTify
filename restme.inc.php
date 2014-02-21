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

class Restme {

	private $endpoints = array();
	private $route_params = array();
	private $error = array();

	public function __construct () {
		$la_script_path = explode('/', $_SERVER['SCRIPT_NAME']);
		$la_script_name = array_pop($la_script_path);
		$la_script_name = explode('.', $la_script_name);
		array_pop($la_script_name);
		$ls_script_name = implode('.', $la_script_name);

		$ls_endpoint_file_path = './endpoint/' . $ls_script_name . '.inc.php';
		if (!file_exists($ls_endpoint_file_path)) {
			header('HTTP/1.1 500 Internal Server Error');
			die(json_encode(array('error' => array("Cannot find corresponding endpoint file: $ls_endpoint_file_path")))); // Convert to json 
		}
		include $ls_endpoint_file_path;
		$this->endpoint_class = new $ls_script_name;

		$this->method = strtoupper($_SERVER['REQUEST_METHOD']);
		$this->true_method = (!empty($_SERVER['X-HTTP-Method-Override'])) ? strtoupper($_SERVER['X-HTTP-Method-Override']) : $this->method;
		$this->version = (!empty($_SERVER['Accept-Version'])) ? $_SERVER['Accept-Version'] : '0.0.1';

		$this->query = $this->get_query();

		$li_path_length = strlen(implode('/', $la_script_path));
		$la_request_uri = parse_url(substr($_SERVER['REQUEST_URI'], $li_path_length)); // get rid of the path up to the file.
		$la_request_uri = explode('/', trim($la_request_uri['path'], '/'));
		
		// $this->url_parts = new RURL_mapper($la_request_uri);
		$this->url_parts = $la_request_uri;
	}

	protected function get_query () {
		switch ($this->method) {
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
			if (is_array($la_route_pattern)) {
				$this->add_error(array("la_route_pattern" => $la_route_pattern));
				foreach ($la_route_pattern as $ls_segment) {
					$this->add_error(array("ls_segment" => $ls_segment));
					if (is_array($la_url_parts) && count($la_url_parts)) {
						$ls_param = array_shift($la_url_parts);
						$this->add_error(array("ls_param" => $ls_param));

						if (preg_match('/^\:(.*)/', $ls_segment, $lm_segment_key)) {
							$la_return_params[$lm_segment_key[1]] = $ls_param;
						}
						elseif ($ls_segment !== $ls_param) {
							$this->add_error("Not matched!");
							// Not matched
							continue 2;
						}
					}
					else {
						// Would be good to put this in a function
						// Also add any remaining url parts to key/value $la_return_params
						$this->add_error(array("la_return_params" => $la_return_params));
						$this->route_params = $la_return_params;
						return $ls_endpoint;
					}
				}
	
				$this->add_error(array("la_return_params" => $la_return_params));
				$this->route_params = $la_return_params;
				return $ls_endpoint;
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
			
			$this->add_error("Could not add route; method: $ps_method, route: " . print_r($pm_route, true) . ", endpoint: $ps_endpoint;");
			return false;
		}


		if (is_array($pm_route)) {
			// shouldn't need to check this but we love safety.
			if (array_key_exists('path', $pm_route)) {
				$ls_method = $pm_route['path'];
			}

			if (array_key_exists('version', $pm_route)) {
				$ls_version = $pm_route['version'];
			}
		}
		else {
			$ls_method = $ps_method;
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

		$this->endpoints[$ps_method][$ls_version][$pm_route] = $ps_endpoint;
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
					
					$this->add_error("Endpoint not added for: " . print_r($this->url_parts, true));
				}


			}
			else {
				$lb_endpoint_reached = false; 
				
				$this->add_error("Route not added for: " . print_r($this->url_parts, true));
			}
		}

		header('Content-Type: application/json');
		// not matched:
		if (!$lb_endpoint_reached) {
			header('HTTP/1.0 404 Not Found');
			echo json_encode($this->error);
			exit();
		}
		//echo json_encode($this);
		// check endpoint return (should be array)
		if (is_array($la_endpoint_result) && !empty($la_endpoint_result)) {
			// echo json out
			echo json_encode($la_endpoint_result);
		}
	}
}
