# RESTify class
---

an abstract class that ['RESTifies'][what-is-rest] a resource, function, or class method.

### endpoint()

  ['RESTifies'][what-is-rest] the function, method or resource that uses it.

  * @access public
  * @param string **action**
  * @return mixed
  

### rurl_mapper()

  a protected method that returns a RURL_mapper object.
  
  * @access protected
  * @return *[RURL_mapper](#rurl_mapper-class)* object


### RURL_mapper class
---

used to map *[RESTful URLs](#what-are-restful-urls)*

  
##### query()
  
  sets the query property to map. 
    
  * @access public
  * @param array data
  * @return void
  

Sample Usage:


    // ..previous code
    
    $resource = 'http://example.com/some-rand-segment/anthr-rand-segment/products/17';
    
    $resource = parse_url($resource);
    
    $request_uri = $resource['path'];
    
    $uri_array = trim(explode('/', $request_uri), '/');
    
    $this->rurlm->query($uri_array);

    // next code..
    
  
##### map()
 
  maps the query property
    
  * @access public
  * @param array data
  * @return array
  

Sample Usage:


    // ..previous code
    $data = array(
      '0'=>'just-another-segment',
      '1'=>'just-another-segment',
      '2'=>'category',
      '3'=>'id'
    );
    
    $mapped_data = $this->rurlm->map($data);
    
    print_r($mapped_data);
    // next code..
    
  
Result:
  
  
    Array(
      'just-another-segment' => Array(
        'some-rand-segment',
        'anthr-rand-segment'
      ),
      'category' => 'products',
      'id' => '17'
    );


HOW TO:
---

* Create an *[Endpoint class](#what-is-an-endpoint-class)*
        

        class My_Endpoint extends Restify {
        
          public function onGet($data = null){
            // GET method
          }
          public function onPost($data = null){
            // POST method
          }
          public function onPut($data = null){
            // PUT method
          }
          public function onDelete($data = null){
            // DELETE method
          }
        
        }

* Apply the endpoint to a function or method.

        // ..previous code
        
          Restify::endpoint('My_Endpoint');
        
        // next code..

* Send some "actions" to the [RESTified][what-is-rest] [Resource][what-is-a-resource]!

        GET /active_endpoint HTTP/1.1
        Host: example.com

What are [RESTful URLs][what-are-clean-urls]?
---

also known as Clean URLs, are purely structural URLs that do not contain a query string and instead contain only the path of the [Resource][what-is-a-resource]

  an example of a clean url
  > http://example.com/some-rand-segment/anthr-rand-segment/products/17
    
  which is equivalent to..
  > http://example.com/some-rand-segment/anthr-rand-segment/?category=products&id=17
     
  The problem is the global variable *$_GET* wont recognize */products* and */17* as *category* and *id* respectively. **Restify** is capable of mapping these *[RESTful URLs](#what-are-restful-urls)* using *[RURL_mapper class](#rurl_mapper-class)*.
  
What is an Endpoint class?
---

You might be confused about the **Endpoint Class** that I have been mentioning (alot). It is the name I call to classes that extends the **[Restify Class](#restify-class)**. It consists of 4 (four) required methods:

* onGet
* onPost
* onPut
* onDelete

these methods need only one parameter (though not required) to read the input or query sent by the HTTP request. You can optionally return a value which in turn be passed down to the respective [Restify::endpoint()](#endpoint) called.

[what-is-a-resource]: http://en.wikipedia.org/wiki/Resource_%28Web%29
[what-are-clean-urls]: http://en.wikipedia.org/wiki/Clean_URL
[what-is-rest]: http://en.wikipedia.org/wiki/Representational_state_transfer
