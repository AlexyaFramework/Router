<?php
namespace Alexya\Router;

use \Alexya\Router\Exceptions\NoRouteMatch;

/**
 * Router class.
 *
 * The router translates the HTTP requests and routes them through
 * different specified callbacks until one can handle it.
 *
 * First you'll need to instance a Router object that will route the requests.
 *
 * The constructor accepts as parameter a string being the base path.
 *
 * Example:
 *
 *     $Router = new \Alexya\Router\Router();
 *     // will route all requests of `/`
 *     // /forum
 *     // /forum/thread/1
 *     // /blog
 *     // /blog/post/1
 *
 *     $Router = new \Alexya\Router\Router("blog");
 *     // will route all requests of `/blog` (if `/forum` is requested, it will be ignored).
 *     // /blog
 *     // /blog/post/1
 *
 * Once the router has been instantiated you will have to add the routes, you can do this using the method `add`.
 * The first parameter is the regular expression to match, the second parameter is the callback to execute if the
 * regular expression is matched, the third parameter is an array containing the methods where the regular expression should
 * be tested (if it's empty it will be tested globaly).
 *
 * Example:
 *
 *    $Router = new \Alexya\Router\Router("blog");
 *    $Router->add("/post/([0-9]*)", function($post_id) {
 *        echo "Requested post: {$post_id}";
 *    });
 *    // Matches:
 *    //  GET  /blog/post/
 *    //  GET  /blog/post/1
 *    //  POST /blog/post/3416321341
 *    //
 *    // Doesn't match:
 *    //  GET  /blog/post/a
 *    //  POST /post/
 *    //  GET  /post/1
 *
 * You can also use the following methods for adding routes (all of the accepts as parameter the regular expression and the callback):
 *.
 *  * `get`: Adds a route for `GET` requests
 *  * `post`: Adds a route for `POST` requests.
 *  * `head`: Adds a route for `HEAD` requests.
 *  * `put`: Adds a route for `PUT` requests.
 *  * `delete`: Adds a route for `DELETE` requests.
 *
 * Once all routes have been added you will need to call the `route` method which will parse the request through all
 * routes, if the request matches a route, its callback will be executed, if not, it will throw an exception of
 * type `\Alexya\Router\Exceptions\NoRouteMatch`.
 *
 * To avoid a exception to be thrown, you can use the method `setDefault` which accepts as parameter the
 * `\Alexya\Router\Route` object to be executed if the request doesn't match any route.
 * Alternatively, you can use the method `add` and send `{DEFAULT}` as the regular expression.
 *
 * Example:
 *
 *    $Router = new \Alexya\Router\Router("blog");
 *
 *    $Router->add("/post/([0-9]*)", function($id) {
 *        echo "Requested post: {$id}";
 *    }, "GET");
 *
 *    $Router->add("{DEFAULT}", function() {
 *        echo "The page doesn't exist!";
 *    });
 *
 *    //  |           Request          |         Response       |
 *    //  |----------------------------|------------------------|
 *    //  | GET  /blog/post/           | Requested post:        |
 *    //  | GET  /blog/post/1          | Requested post: 1      |
 *    //  | POST /blog/post/3416321341 | The page doesn't exist |
 *    //  | GET  /blog/post/a          | The page doesn't exist |
 *    //  | POST /post/                | The page doesn't exist |
 *    //  | GET  /post/1               | The page doesn't exist |
 *
 * @see \Alexya\Router\Route For more information about the parameters sent to the `add` method
 *
 * @author Manulaiko <manulaiko@gmail.com>
 */
class Router
{
    /**
     * Routes array
     *
     * Contains all loaded routes
     *
     * @var array Routes array
     */
    private $_routes = [];

    /**
     * Base URL
     *
     * @var string
     */
    private $_basePath = "";

    /**
     * Current relative URL
     *
     * @var string
     */
    private $_path = "";

    /**
     * Default route.
     *
     * Route to execute if any of the other routes are matched.
     *
     * @var \Alexya\Router\Route
     */
    private $_defaultRoute;

    /**
     * Constructor.
     *
     * The parameter is the base path of all routes.
     * All routes added will be executed if they match `$basePath . $regex`:
     *
     *     $Router = new \Alexya\Router\Router();
     *     // will route all requests of `/`
     *     // /forum
     *     // /forum/thread/1
     *     // /blog
     *     // /blog/post/1
     *
     *     $Router = new \Alexya\Router\Router("blog");
     *     // will route all requests of `/blog` (if `/forum` is requested, it will be ignored).
     *     // /blog
     *     // /blog/post/1
     *
     * @param string $basePath Route's base path
     */
    public function __construct(string $basePath = "")
    {
        $this->_basePath = $basePath;

        $path = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        $path = substr($path, strlen($basePath));

        $this->_path = $path;
    }

    /**
     * Test all routes until any of them matches (or there's a default route)
     *
     * @return mixed Route's callback return
     *
     * @return \Alexya\Router\Exceptions\NoRouteMatch If no route can handle the request
     */
    public function route()
    {
        foreach($this->routes as $route) {
            if($route->matches($this->_path)) {
                return $route->execute();
            }
        }

        if(!is_null($this->_defaultRoute)) {
            $route->isMatched = true;
            $this->_defaultRoute->execute();
        }

        throw new NoRouteMatch($this->_path);
    }

    /**
     * Adds a route to the array.
     *
     * There are 2 ways for calling this method:
     *
     *  - Adding a single route.
     *  - Adding multiple routes.
     *
     * To add a single route you need to provide at least the first 2 arguments:
     *
     *  - The regular expression that the router should match.
     *  - The callback to call when the expression is matched.
     *
     * Optionally you can send a 3rd parameter that can be either an array or a string
     * this parameter will contain the method(s) on which the expression should me matched.
     *
     * To add multiple routes you need to provide a single parameter that is an array
     * which contains all the routes to match, each index of the array is an array that
     * contains at least 2 index:
     *
     *  - The regular expression that the router should match.
     *  - The callback to call when the expression is matched.
     *
     * Optionally you can add a 3rd index that can be either an array or a string
     * this index will contain the method(s) on which the expression should me matched.
     *
     * Example:
     *
     *     $Router->add("/blog/post/([0-9]*)", function($id) {
     *         echo "Requested post: {$id}";
     *     });
     *     $Router->add("/forum/thread/([0-9]*)", function($id) {
     *         echo "Requested thread: {$id}";
     *     }, ["GET", "POST"]);
     *
     *     $Router->add([
     *         [
     *             "/blog/post/([0-9]*)",
     *             function($id) {
     *                 echo "Requested post: {$id}";
     *             }
     *         ],
     *         [
     *             "/forum/thread/([0-9]*)",
     *             function($id) {
     *                 echo "Requested thread: {$id}";
     *             },
     *             ["GET", "POST"]
     *         ]
     *     ]);
     *
     * @param mixed|string $regexp   Regular expression to match
     * @param callable     $callback Callback to execute if $expr is matched
     * @param array|string $methods  Methods on which the expression should be matched
     *
     * @throws \InvalidArgumentException If `$callback` is null, any of the `$regexp` index isn't an array or isn't valid
     */
    public function add($regexp, callable $callback = null, $methods = null)
    {
        if(!is_array($regexp)) {
            if(is_null($callback)) {
                throw new InvalidArgumentException("Route callback can't be null!");
            }

            $route = new Route($regexp, $callback, $methods);

            $this->_routes[] = $route;
            if($regexp == "{DEFAULT}") {
                $this->_defaultRoute = $route;
            }

            return;
        }

        foreach($regexp as $route) {
            if(!is_array($route)) {
                throw new InvalidArgumentException("Couldn't parse router array! Each index must be an array!");
            }
            if(count($route) < 2) {
                throw new InvalidArgumentException("Each route must have, at least 2 indexes: regexp and callback!");
            }
            if(!is_string($route[0])) {
                throw new InvalidArgumentException("Couldn't parse router array! First index must be a reg exp");
            }
            if(!is_callable($route[1])) {
                throw new InvalidArgumentException("Couldn't parse router array! Second index must be a callback");
            }

            $regexp   = $route[0];
            $callback = $route[1];
            $methods  = ($route[2] ?? null);

            $route = new Route($regexp, $callback, $methods);

            $this->_routes[] = $route;
            if($regexp == "{DEFAULT}") {
                $this->_defaultRoute = $route;
            }
        }
    }

    /**
     * Sets the default route.
     *
     * @param \Alexya\Router\Route $route Default route.
     */
    public function setDefault(Route $route)
    {
        $this->_defaultRoute = $route;
    }

    /////////////////////
    // Start fallbacks //
    /////////////////////
    /**
     * Add a route for GET requests
     *
     * @param string   $regexp
     * @param callback $callback
     */
    public function get(string $regexp, callable $callback)
    {
        $this->add($regexp, $callback, 'GET');
    }

    /**
     * Add a route for POST requests
     *
     * @param string   $regexp
     * @param callback $callback
     */
    public function post(string $regexp, callable $callback)
    {
        $this->add($regexp, $callback, 'POST');
    }

    /**
     * Add a route for HEAD requests
     *
     * @param string   $regexp
     * @param callback $callback
     */
    public function head(string $regexp, callable $callback)
    {
        $this->add($regexp, $callback, 'HEAD');
    }

    /**
     * Add a route for PUT requests
     *
     * @param string   $regexp
     * @param callback $callback
     */
    public function put(string $regexp, callable $callback)
    {
        $this->add($regexp, $callback, 'PUT');
    }

    /**
     * Add a route for DELETE requests
     *
     * @param string   $regexp
     * @param callback $callback
     */
    public function delete(string $regexp, callable $callback)
    {
        $this->add($regexp, $callback, 'DELETE');
    }
    ///////////////////
    // End fallbacks //
    ///////////////////
}
