<?php namespace Kevupton\LaravelSwagger;

use Swagger\Annotations\AbstractAnnotation;
use Swagger\Annotations\Delete;
use Swagger\Annotations\Get;
use Swagger\Annotations\Head;
use Swagger\Annotations\Operation;
use Swagger\Annotations\Options;
use Swagger\Annotations\Patch;
use Swagger\Annotations\Post;
use Swagger\Annotations\Put;
use Swagger\Context;

class DynamicMethod {

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
        $this->links = $this->_register_links($this->data);
    }

    /**
     * Register the reference links from the input values.
     *
     * @param array $obj
     * @return array
     */
    private function _register_links(&$obj) {
        $array = [];

        foreach ($obj as $key => &$value) {
            if (is_array($value) || $value instanceof AbstractAnnotation || $value instanceof \stdClass) {
                $temp = $this->_register_links($value);
                foreach ($temp as $temp_key => $temp_value) {
                    if (isset($array[$temp_key])) {
                        $array[$temp_key] = array_merge($array[$temp_key], $temp_value);
                    } else {
                        $array[$temp_key] = $temp_value;
                    }
                }
            } else if (preg_match('/\{\{(.*?)\}\}/', $value, $matches)) {
                $id = $matches[1];
                //add to the list of links for that specific id
                if (isset($array[$id])) {
                    $array[$id][] = &$value;
                } else {
                    $array[$id] = [&$value];
                }
            }
        }

        return $array;
    }

    /**
     * Creates a new Dynamic GET Method
     *
     * @param array $data
     * @return DynamicMethod
     */
    public static function GET(array $data = array()) {
        return new self(Get::class, $data);
    }

    /**
     * Creates a new Dynamic POST Method
     *
     * @param array $data
     * @return DynamicMethod
     */
    public static function POST(array $data = array()) {
        return new self(Post::class, $data);
    }

    /**
     * Creates a new Dynamic PUT Method
     *
     * @param array $data
     * @return DynamicMethod
     */
    public static function PUT(array $data = array()) {
        return new self(Put::class, $data);
    }

    /**
     * Creates a new Dynamic PATCH Method
     *
     * @param array $data
     * @return DynamicMethod
     */
    public static function PATCH(array $data = array()) {
        return new self(Patch::class, $data);
    }

    /**
     * Creates a new Dynamic DELETE Method
     *
     * @param array $data
     * @return DynamicMethod
     */
    public static function DELETE(array $data = array()) {
        return new self(Delete::class, $data);
    }

    /**
     * Creates a new Dynamic HEAD Method
     *
     * @param array $data
     * @return DynamicMethod
     */
    public static function HEAD(array $data = array()) {
        return new self(Head::class, $data);
    }

    /**
     * Creates a new Dynamic OPTIONS Method
     *
     * @param array $data
     * @return DynamicMethod
     */
    public static function OPTIONS(array $data = array()) {
        return new self(Options::class, $data);
    }

    /**
     * Sets the value of key.
     *
     * @param $key
     * @param $value
     */
    public function set($key, $value) {

        foreach ($this->links[$key] as &$current) {
            //if the string is more than the specified key then do a replace
            if (strlen($current) > (strlen($key) + 4)) {
                $value = str_replace("{{".$key."}}",$value, $current);
            }
            $current = $value;
        }

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


    /**
     * Makes the Method
     *
     * @param Context $context
     * @return Operation
     */
    public function make(Context $context) {
        $class = $this->method;
        $this->_make_parent($this->data, $context);
        return new $class($this->data);
    }

    /**
     * Loop through each value and assign the context to the parent.
     *
     * @param $array
     * @param Context $context
     */
    private function _make_parent(&$array, Context $context) {
        foreach ($array as $key => $value) {
            if ($value instanceof AbstractAnnotation) {
                $child = new Context(['nested'=>true, 'class' => $context->class], $context);
                $value->_context = $child;
                $this->_make_parent($value, $child);
            } else if (is_array($value)) {
                $this->_make_parent($value, $context);
            }
        }
    }

    /**
     * Sets or gets the data that is to be inserted.
     *
     * @param string $key
     * @param mixed $value
     * @return mixed|null
     */
    public function data($key, $value = null) {
        if (is_null($value)) {
            return isset($this->data[$key])? $this->data[$key]: null;
        } else {
            return $this->data[$key] = $value;
        }
    }
}