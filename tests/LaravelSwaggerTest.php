<?php


class LaravelSwaggerTest extends TestCase
{
    public function testScan() {
        \Kevupton\LaravelSwagger\scan(app_path('../tests/swagger'), [
            'models' => [
                \Mevu\Logic\Core\Models\Match::class
            ]
        ]);
    }
}
