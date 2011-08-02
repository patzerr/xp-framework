<?php
/* This class is part of the XP framework
 *
 * $Id$
 */

  uses(
    'util.Hashmap',
    'webservices.rest.routing.RestPath',
    'webservices.rest.routing.RestRouter',
    'webservices.rest.routing.RestRouting',
    'webservices.rest.routing.RestMethodRoute'
  );
  
  /**
   * REST router based on class and method annotations
   *
   * @test    xp://net.xp_framework.unittest.rest.RestAnnotationRouterTest
   * @purpose Router
   */
  class RestAnnotationRouter extends Object implements RestRouter {
    protected $base= '';
    protected $table= NULL;
    
    /**
     * Constructor
     * 
     */
    public function __construct() {
      $this->table= new RestRouting();
    }
    
    /**
     * Configure router
     * 
     * @param string setup The setup string
     * @param string base The base URI
     */
    public function configure($setup, $base= '') {
      $package= Package::forName($setup);
      $this->base= rtrim($base, '/');
      
      foreach ($package->getClasses() as $handler) {
        if (!$handler->hasAnnotation('webservice')) continue;
        
        foreach ($handler->getMethods() as $method) {
          if (
            !$method->hasAnnotation('webmethod', 'method') ||
            !$method->hasAnnotation('webmethod', 'path')
          ) continue;
          
          // Build argument and injection list
          $args= new RestRoutingArgs();
          foreach ($method->getParameters() as $param) {
            $args->addArgument($param->getName(), $param->getType());
          }
          if ($method->hasAnnotation('webmethod', 'inject')) foreach ($method->getAnnotation('webmethod', 'inject') as $name) {
            $args->addInjection($name);
          }
          
          // Add routing to table
          $this->table->addRoute(
            $method->getAnnotation('webmethod', 'method'),
            $method->getAnnotation('webmethod', 'path'),
            new RestMethodRoute($method),
            $args
          );
        }
      }
    }
    
    /**
     * Test if route exists
     *
     * @param string method The method
     * @param string path The path
     * @return bool 
     */
    public function hasRoutesFor($method, $path) {
      return $this->table->hasRoutings($method, $path);
    }
    
    /**
     * Return routes for given request and response
     * 
     * @param webservices.rest.transport.HttpRequestAdapter request The request
     * @param webservices.rest.transport.HttpResponseAdapter response The response
     * @return webservices.rest.RestRoute[]
     */
    public function routesFor($request, $response) {
      $method= $request->getMethod();
      $path= substr($request->getPath(), strlen($this->base));
      
      if ($this->table->hasRoutings($method, $path)) {
        return $this->table->getRoutings($method, $path);
      }
      
      return array();
    }
  }
?>
