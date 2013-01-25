<?php
class RURL_mapper {

	public function __construct($query = null){
		$this->query($query);
	}

	public function query($query){
		$this->query = $query;
	}

	public function map($names = null){

		if(!is_array($this->query)) die('RURL_Mapper :: Cannot map invalid/empty query');

		$names = (is_array($names)) ? $names : func_get_args();
		
		return $this->_sub_map($names);
	}

	protected function _sub_map($names){
		$params = array();
		foreach($names as $id => $name){
			$query = !@empty($this->query[$id]) ? $this->query[$id] : '';
			if(isset($params[$name])){
				if(! is_array($params[$name])){
					$temp = $params[$name];
					$params[$name] = array();
					$params[$name][] = $temp;
				} 
				$params[$name][] = $query;
			} else {
				$params[$name] = $query;
			}
		}
		return $params;
	}
}
?>