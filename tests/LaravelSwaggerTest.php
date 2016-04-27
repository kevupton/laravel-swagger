<?php


class LaravelSwaggerTest extends TestCase
{
    public function testEmptyScan() {
        \Kevupton\LaravelSwagger\scan(app_path('../tests/swagger'));
    }
}
