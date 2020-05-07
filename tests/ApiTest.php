<?php

namespace MichaelBelgium\YoutubeAPI\Tests;

use Orchestra\Testbench\TestCase;
use MichaelBelgium\YoutubeAPI\YoutubeAPIServiceProvider;

class ApiTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [YoutubeAPIServiceProvider::class];
    }

    public function testGetRoute() {
        $response = $this->post('/ytconverter/convert');
        $response->assertSuccessful();
    }
}
