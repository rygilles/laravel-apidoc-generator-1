<?php

namespace Mpociot\ApiDoc\Generators;

use Exception;

class DingoGenerator extends AbstractGenerator
{
    /**
     * @param \Illuminate\Routing\Route $route
     * @param array $bindings
     * @param array $headers
     * @param bool $withResponse
     *
     * @return array
     */
    public function processRoute($route, $bindings = [], $headers = [], $withResponse = true)
    {
        $response = '';

	    $routeAction = $route->getAction();
	    $routeApiDocsSettings = $this->getRouteApiDocsSettings($routeAction['uses']);

	    if ($withResponse && !in_array('no_call', $routeApiDocsSettings)) {
            try {
                $response = $this->getRouteResponse($route, $bindings, $headers);
            } catch (Exception $e) {
            }
        }

        $routeAction = $route->getAction();

	    if (is_object($response)) {
		    $response = $response->content();
	    }
        $routeGroup = $this->getRouteGroup($routeAction['uses']);
        $routeDescription = $this->getRouteDescription($routeAction['uses']);

        return $this->getParameters([
            'id' => md5($route->uri().':'.implode($route->getMethods())),
            'resource' => $routeGroup,
            'title' => $routeDescription['short'],
            'description' => $routeDescription['long'],
            'methods' => $route->getMethods(),
            'uri' => $route->uri(),
            'parameters' => [],
            'response' => $response,
	        'bindedUri' => $this->addRouteModelBindings($route, $bindings),
	        'bindings' => $bindings,
        ], $routeAction, $bindings);
    }

	/**
	 * Return route settings for documentation generation from route docblock
	 *
	 * @param  \Illuminate\Routing\Route  $route
	 * @return string[]
	 * @todo Other route settings in docblock ? Put settings in a config file ?
	 */
	protected function getRouteApiDocsSettings($route)
	{
		list($class, $method) = explode('@', $route);
		$reflection = new ReflectionClass($class);
		$reflectionMethod = $reflection->getMethod($method);

		$comment = $reflectionMethod->getDocComment();
		$phpdoc = new DocBlock($comment);

		$settings = [];

		// Document the route but don't make a call (prevent UPDATE, DELETE, etc.)
		if ($phpdoc->hasTag('ApiDocsNoCall')) {
			$settings[] = 'no_call';
		}

		return $settings;
	}

    /**
     * Prepares / Disables route middlewares.
     *
     * @param  bool $disable
     *
     * @return  void
     */
    public function prepareMiddleware($disable = true)
    {
        // Not needed by Dingo
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function callRoute($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $dispatcher = app('Dingo\Api\Dispatcher')->raw();

        collect($server)->map(function ($key, $value) use ($dispatcher) {
            $dispatcher->header($value, $key);
        });

        return call_user_func_array([$dispatcher, strtolower($method)], [$uri]);
    }

    /**
     * {@inheritdoc}
     */
    public function getUri($route)
    {
        return $route->uri();
    }

    /**
     * {@inheritdoc}
     */
    public function getMethods($route)
    {
        return $route->getMethods();
    }
}
