<?php

class MyEndpoint extends Restify {
	
	public function onGet($data = null){
		
		$mapped_data = $this->rurl_mapper()->map(
			array(
				'2'=>'FEC',
				'0'=>'folder',
				'1'=>'folder',
				'3'=>'user_id',
			)
		);

		echo "Method: GET";
		echo '<pre>';
			print_r($data);
			print_r($mapped_data);
		echo '</pre>';
	}
	public function onPost($data = null){
		echo "Method: POST";
		echo '<pre>';
			print_r($data);
		echo '</pre>';
	}
	public function onPut($data = null){
		echo "Method: PUT";
		echo '<pre>';
			print_r($data);
		echo '</pre>';
	}
	public function onDelete($data = null){
		echo "Method: DELETE";
		echo '<pre>';
			print_r($data);
		echo '</pre>';
	}

}

?>