<?php namespace Kevupton\LaravelSwagger;

use Kevupton\LaravelSwagger\Exceptions\DynamicMethodException;
use Swagger\Annotations\AbstractAnnotation;

class DynamicMethod {

    /** @var DynamicMethod[] the list of registered methods */
    private static $methods = [];

    /** @var string */
    private $method;

    /** @var array all the data */
    private $data = [];

    /** @var array a link of references to method */
    private $links = [];

    /**
     * DynamicMethod constructor.
     *
     * @param string $method
     * @param array $data
     */
    public function __construct($method, array $data = array()) {
        $this->method = $method;
        $this->data = $data;
        $this->links = $this->_register_links($data);
    }

    /**
     * Register the reference links from the input values.
     *
     * @param array $obj
     * @return array
     */
    private function _register_links(array $obj) {
        $array = [];

        foreach ($obj as $key => &$value) {
            if (is_array($value) || $value instanceof AbstractAnnotation || $value instanceof \stdClass) {
                $array = array_merge($array, $this->read($value));
            } else if (preg_match('/\{\{(.*?)\}\}/', $value, $matches)) {
                $id = $matches[1];
                $array[$id] = &$value;
            }
        }

        return $array;
    }

    /**
     * Creates a new Dynamic GET Method
     *
     * @param string $name the ID of the method to store
     * @param array $data
     * @param callable $method
     * @return DynamicMethod
     * @throws DynamicMethodException
     */
    public static function GET($name, array $data = array(), callable $method) {
        //check if it already exists
        if (is_null($val = self::_get($name))) {
            if (!empty($data)) { //validate the data exists
                $class = new self('GET', $data, $method);
            } else {
                throw new DynamicMethodException("Dynamic Method not found and the data is empty to create one.");
            }

            return self::$methods[$name] = $class;
        }

    }

    /**
     * Gets the DynamicMethod if it exists, else returns NULL
     *
     * @param $name
     * @return DynamicMethod|null
     */
    private static function _get($name) {
        return (isset(self::$methods[$name]))? self::$methods[$name]: null;
    }

    /**
     * Sets the value of key.
     *
     * @param $key
     * @param $value
     */
    public function set($key, $value) {

        $this->links[$key] = $value;

    }

    /**
     * Gets all the keys in the links array
     *
     * @return array
     */
    public function keys()
    {
        return array_keys($this->links);
    }
}