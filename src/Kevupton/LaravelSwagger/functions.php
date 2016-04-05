<?php namespace Kevupton\LaravelSwagger;

use Mevu\Logic\Core\Models;
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
    $processors = @$options['processors'] ?: Analysis::processors();

    /** Edit the processors to be called. Add the handler to the start */
    $options['processors'] = array_prepend($processors, new LaravelSwagger($models));

    return \Swagger\scan($directory, $options);
}