<?php namespace Kevupton\LaravelSwagger;

class ValueContainer {

    const CONTAINER = 'swagger_values';


    public static function getValue($class, $key) {

        if (isset($class::$$key)) {
            return $class::$$key;
        } else if (isset($class::{self::CONTAINER})) {
            /** @var ValueContainer $cont */
            $cont = $class::{self::CONTAINER};
            $cont = (new $cont);

            if (isset($cont->$key)) {
                return $cont->$key;
            }
        }

        return null;

    }

}