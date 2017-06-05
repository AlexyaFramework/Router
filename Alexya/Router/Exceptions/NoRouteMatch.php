<?php
namespace Alexya\Router\Exceptions;

/**
 * No route match exception.
 *
 * This exception is thrown when the router didn't find any route that matches the request.
 *
 * Example:
 *
 * ```php
 * try {
 *     $Router = new \Alexya\Router\Router();
 *     $Router->route()
 * } catch(NoRouteMatch $e) {
 *     echo "The request doesn't match any route!";
 * }
 * ```
 *
 * @author Manulaiko <manulaiko@gmail.com>
 */
class NoRouteMatch extends \Exception
{
    /**
     * Constructor.
     *
     * @param string $path Requested path.
     */
    public function __construct(string $path)
    {
        parent::__construct("The request `{$path}` doesn't match any route!");
    }
}
