# Router
Alexya's routing components

## Contents
 - [Instantiating Router objects](#instantiating_router_objects)
 - [Adding routes](#adding_routes)
   - [Adding a single route](#adding_a_single_route)
   - [Adding multiple routes](#adding_multiple_routes)
 - [Matching routes](#matching_routes)
 - [The default route](#the_default_route)
 - [Chainable routes](#chainable_routes)

<a name="instantiating_router_objects"></a>
## Instantiating Router objects
The router translates the HTTP requests and routes them through different specified callbacks until one can handle it.

First you'll need to instance a Router object that will route the requests.

The constructor accepts as parameter a string being the base path.

Example:

```php
<?php
$Router = new \Alexya\Router\Router();
// will route all requests of `/`
// /forum
// /forum/thread/1
// /blog
// /blog/post/1

$Router = new \Alexya\Router\Router("blog");
// will route all requests of `/blog` (if `/forum` is requested, it will be ignored).
// /blog
// /blog/post/1
```

<a name="adding_routes"></a>
## Adding routes
Once the router has been instantiated you will have to add the routes, you can do this using the method `add`.
The first parameter is the regular expression to match, the second parameter is the callback to execute if the
regular expression is matched, the third parameter is an array containing the methods where the regular expression should
be tested (if it's empty it will be tested globaly).

There are 2 ways for calling this method:

 - Adding a single route.
 - Adding multiple routes.

<a name="adding_a_single_route"></a>
### Adding a single route
To add a single route you need to provide at least the first 2 arguments:

 - The regular expression that the router should match.
 - The callback to call when the expression is matched.

Optionally you can send a 3rd parameter that can be either an array or a string, this parameter will contain
the method(s) on which the expression should me matched.

Example:

```php
<?php

$Router->add("/blog/post/([0-9]*)", function($id) {
    echo "Requested post: {$id}";
});

$Router->add("/forum/thread/([0-9]*)", function($id) {
    echo "Requested thread: {$id}";
}, ["GET", "POST"]);
```

<a name="adding_multiple_routes"></a>
### Adding multiple routes
To add multiple routes you need to provide a single parameter that is an array which contains all the routes to match,
each index of the array is an array that contains at least 2 index:

 - The regular expression that the router should match.
 - The callback to call when the expression is matched.

Optionally you can add a 3rd index that can be either an array or a string, this index will contain the method(s)
on which the expression should be matched.

Example:

```php
<?php

$Router->add([
    [
        "/blog/post/([0-9]*)",
        function($id) {
            echo "Requested post: {$id}";
        }
    ],
    [
        "/forum/thread/([0-9]*)",
        function($id) {
            echo "Requested thread: {$id}";
        },
        ["GET", "POST"]
    ]
])
```

<a name="matching_routes"></a>
## Matching routes
Once all routes have been added you will need to call the `route` method which will parse the request through all
routes, if the request matches a route, its callback will be executed, if not, it will throw an exception of
type `\Alexya\Router\Exceptions\NoRouteMatch`.

<a name="the_default_route"></a>
## The default route
To avoid a exception to be thrown, you can use the method `setDefault` which accepts as parameter the
`\Alexya\Router\Route` object to be executed if the request doesn't match any route.
Alternatively, you can use the method `add` and send `{DEFAULT}` as the regular expression.

Example:

```php
<?php
$Router = new \Alexya\Router\Router("blog");

$Router->add("/post/([0-9]*)", function($id) {
    echo "Requested post: {$id}";
}, "GET");

$Router->add("{DEFAULT}", function() {
    echo "The page doesn't exist!";
});

//  |           Request          |         Response       |
//  |----------------------------|------------------------|
//  | GET  /blog/post/           | Requested post:        |
//  | GET  /blog/post/1          | Requested post: 1      |
//  | POST /blog/post/3416321341 | The page doesn't exist |
//  | GET  /blog/post/a          | The page doesn't exist |
//  | POST /post/                | The page doesn't exist |
//  | GET  /post/1               | The page doesn't exist |
```

<a name="chainable_routes"></a>
## Chainable routes
Sometimes it's a good idea to have multiple routes work in a single request.
For example, you could check that the page exists in a route, then check if the user is logged
in another, then load the page in another route...

The function `\Alexya\Router\Router::route` can accept as parameter a boolean that tells
if the router will support chainable routes or not.

When the router supports chainable routes it will loop through ALL routes and execute all the routes
that are matched, to stop the router you can let the route callback return `false` or finish the script execution with `die` or `exit`.

Example:

```php
<?php

$router = new \Alexya\Router\Router();
$router->add("(.*)", function() {
    echo "Page requested";
});
$router->add("/User/([a-zA-Z]*)", function($user) {
    echo "User {$user}";

    return false; // Exit chain
});
$router->add("/User/([a-zA-Z]*)/(.*)", function($user, $action) {
    echo "User {$user}, action {$action}";
});

$router->route(true);

// Request URI: /User/test/asdf
// Output:
//  Page requested
//  User test
```
