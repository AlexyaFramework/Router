<?php
namespace Alexya\Router;

/**
 * Route class.
 *
 * This class is used to represent a route.
 *
 * The constructor accepts two parameters:
 *
 *  * A string being the regular expression that matches the route.
 *  * A callback being the closure to execute if the regular expression is matched.
 *
 * You can also send a third parameter that can be either a string or an array with the methods where the
 * route should be tested. Default is `["GET", "POST", "HEAD", "PUT", "DELETE"]`.
 *
 * The method `matches` checks wether the given parameter matches the regular expression.
 * The method `execute` executes the callback.
 *
 * @author Manulaiko <manulaiko@gmail.com>
 */
class Route
{
    /**
     * The regular expression that matches path.
     *
     * @var string
     */
    private $_regexp = "";

    /**
     * The callback function to execute when `$_regexp` is matched.
     *
     * @var callable
     */
    private $_callback;

    /**
     * The matches of `$_regexp`.
     *
     * @var array
     */
    private $_matches = [];

    /**
     * Allowed methods for this route.
     *
     * @var array
     */
    private $_methods = [
        'GET',
        'POST',
        'HEAD',
        'PUT',
        'DELETE'
    ];

    /**
     * Wether the regular expression has been matched or not.
     *
     * @var bool
     */
    public $isMatched = false;

    /**
     * Constructor.
     *
     * @param string       $regexp   Regular expression to test against.
     * @param callable     $callback Function executed if route matches.
     * @param string|array $methods  Methods allowed.
     */
    public function __construct(string $regexp, callable $callback, $methods = null)
    {
        $this->_regexp   = '#^'. $regexp .'/?$#';
        $this->_callback = $callback;

        if(!is_null($methods)) {
            $this->_methods = is_array($methods) ? $methods : [$methods];
        }
    }

    /**
     * Checks whether `$path` matches the regular expression or not.
     *
     * @param string $path Path to test the regular expression.
     *
     * @return bool `true` if `$path` matches the regular expression, `false` if not.
     */
    public function matches(string $path) : bool
    {
        if(
            preg_match($this->_regexp, $path, $this->_matches) &&
            in_array($_SERVER['REQUEST_METHOD'], $this->_methods)
        ) {
            $this->isMatched = true;
        }

        return $this->isMatched;
    }

    /**
     * Execute the callback.
     *
     * The matches function needs to be called before this and return `true`, if not
     * the callback will not be executed.
     *
     * @return mixed Return value of `$this->_callback`.
     */
    public function execute()
    {
        if(
            !$this->isMatched &&
            $this->_regexp != "#^{DEFAULT}$#"
        ) {
            return;
        }

        return ($this->_callback)(... array_slice($this->_matches, 1));
    }
}
