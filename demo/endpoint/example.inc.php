<?php

namespace RestmeEndpoint;

class example {

	protected function process_request ($pa_route_params, $pa_params, $ps_method) {
		$la_rtn = array(
			'route_params' => $pa_route_params
		  , 'request_params' => $pa_params
		);
		$la_rtn['method'] = $ps_method;
		return $la_rtn;
	}
	
	public function get ($pa_route_params, $pa_params) {
		return $this->process_request($pa_route_params, $pa_params, 'get');
	}
	
	public function get_all ($pa_route_params, $pa_params) {
		return $this->process_request($pa_route_params, $pa_params, 'get_all');
	}
	
	public function add ($pa_route_params, $pa_params) {
		return $this->process_request($pa_route_params, $pa_params, 'add');
	}
	
	public function edit ($pa_route_params, $pa_params) {
		return $this->process_request($pa_route_params, $pa_params, 'edit');
	}
	
	public function remove ($pa_route_params, $pa_params) {
		return $this->process_request($pa_route_params, $pa_params, 'remove');
	}
}
