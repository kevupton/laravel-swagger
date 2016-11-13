<?php namespace Kevupton\LaravelSwagger;

use Swagger\Analysis;
use Swagger\Annotations\Swagger;
use Symfony\Component\Finder\Finder;

/**
 * Same as the Swagger\scan however allows for LaravelModels
 *
 * @param string|array|Finder $directory The directory(s) or filename(s)
 * @param array $options
 *   exclude: string|array $exclude The directory(s) or filename(s) to exclude (as absolute or relative paths)
 *   analyser: defaults to StaticAnalyser
 *   analysis: defaults to a new Analysis
 *   processors: defaults to the registered processors in Analysis
 *   models: the laravel models to convert into definitions
 * @return Swagger
 */
function scan($directory, $options = array())
{
    $models = @$options['models'] ?: [];
    $options['processors'] = @$options['processors'] ?:
        array_merge([new LaravelSwagger($models)], Analysis::processors());

    return \Swagger\scan($directory, $options);
}