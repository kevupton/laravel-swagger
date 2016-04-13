<?php namespace Kevupton\LaravelSwagger;

class ValueContainer {

    const CONTAINER = 'swagger_values';


    /**
     * Gets the value associated with a Controller and Key
     *
     * @param string $class The Controller class
     * @param string $key
     * @return mixed
     */
    public static function getValue($class, $key) {

        $values_cont = self::CONTAINER;

        if (isset($class::$$key)) {
            return $class::$$key;
        } else if (isset($class::$$values_cont)) {

            $cont = $class::$$values_cont;
            $cont = (new $cont);

            if (isset($cont->$key)) {
                return $cont->$key;
            }
        }

        return null;

    }

}