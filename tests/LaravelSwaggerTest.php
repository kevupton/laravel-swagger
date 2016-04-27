<?php


class LaravelSwaggerTest extends TestCase
{
    public function testEmptyScan() {
        \Kevupton\LaravelSwagger\scan(__DIR__ . '/swagger');
    }
}
