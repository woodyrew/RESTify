# PHP RestMe class

A php class that ['RESTifies'][what-is-rest] a [resource][what-is-a-resource], thus providing the ability to write RESTful api's in PHP.

The inspiration was from the [node-restify](https://github.com/mcavage/node-restify) and frustration that I couldn't find an equivilent in PHP.
Forked from [kapitanluffy/RESTify](https://github.com/kapitanluffy/RESTify) however it now works more like [node-restify](https://github.com/mcavage/node-restify) than the original project.

It's designed to be compatible with Backbone's built in [sync](http://backbonejs.org/#Sync) and allows for the [emulateHTTP](http://backbonejs.org/#Sync-emulateHTTP) swtich.

## Usage

### Routing
./api/book.php
```php
<?php

include '../lib/Restme.php';

$restme = new Restme\http();

$restme->get('book/serial/:id', 'get');
$restme->get('book/serial', 'get_all');
$restme->post('book/serial', 'add');
$restme->put('book/serial/:id', 'edit');
$restme->delete('book/serial/:id', 'remove');

$restme->response(); // Will be json encoded with appropriate headers
```

The first parameter of the get, post, put and delete is the 'route'.  This doesn't include the directory the file is in, e.g. if the path to your file was ./my/awesome/api/book.php you would put the route to be 'book/some_resource'.

[Read about resources here.][what-is-a-resource]

The second parameter is a string name of the endpoint method, this file should be located in an endpoints directory relative to the routes file.


### Endpoints class file
./api/endpoints/book.inc.php
```php
<?php

class book {

	protected function process_request ($pa_route_params, $pa_params, $ps_method) {
		$la_rtn = array(
			'route_params' => $pa_route_params
		  , 'request_params' => $pa_params
		);
		$la_rtn['method'] = $ps_method;
		return $la_rtn;
	}
	
	public function get ($pa_route_params, $pa_params) {
		return process_request($pa_route_params, $pa_params, 'get');
	}
	
	public function get_all ($pa_route_params, $pa_params) {
		return process_request($pa_route_params, $pa_params, 'get_all');
	}
	
	public function add ($pa_route_params, $pa_params) {
		return process_request($pa_route_params, $pa_params, 'add');
	}
	
	public function edit ($pa_route_params, $pa_params) {
		return process_request($pa_route_params, $pa_params, 'edit');
	}
	
	public function remove ($pa_route_params, $pa_params) {
		return process_request($pa_route_params, $pa_params, 'remove');
	}
}
```

### Access
To access the [resource][what-is-a-resource], the path would be something like:
 > http://example.com/api/book/serial/12fds3
That would get routed to the *get* method.

**Note:** *the [Clean url][what-are-clean-urls]*

## FAQ

### My server's not setup for PUT and DELETE
You're not alone.

You can still provide PUT and DELETE via POST with a custom header X-HTTP-Method-Override.
e.g.
```
X-HTTP-Method=POST
X-HTTP-Method-Override=PUT
```

### I need to provide version of my api!
No worries, just declare your route like this:
```php
$restme->post(array('path' => 'book/serial', 'version' => '1.0.0', 'script_add_v1');
```
**Note:** *At the moment it uses strict matching but will provide fuzzy matching like '1.x'.*


### What are [RESTful URLs][what-are-clean-urls]?

Also known as Clean URLs, are purely structural URLs that do not contain a query string and instead contain only the path of the [Resource][what-is-a-resource]

  an example of a clean url
  > http://example.com/some-rand-segment/anthr-rand-segment/products/17
    
  which is equivalent to..
  > http://example.com/some-rand-segment/anthr-rand-segment/?category=products&id=17
     
  The problem is the global variable *$_GET* wont recognize */products* and */17* as *category* and *id* respectively. **RestMe** is capable of mapping these *[RESTful URLs](#what-are-restful-urls)* - good times.
  

[what-is-a-resource]: http://en.wikipedia.org/wiki/Resource_%28Web%29
[what-are-clean-urls]: http://en.wikipedia.org/wiki/Clean_URL
[what-is-rest]: http://en.wikipedia.org/wiki/Representational_state_transfer
