<?php
namespace Terrazza\Component\HttpRouting\OpenApi\Tests\Reader;
use PHPUnit\Framework\TestCase;
use Terrazza\Component\HttpRouting\OpenApi\Tests\_Mocks\OpenApiReader;

class OpenApiReaderRouteTest extends TestCase {

    function testGetRoutes() {
        $reader             = OpenApiReader::getReader(OpenApiReader::yamlFileName, false);
        $routes             = $reader->getRoutes();
        $this->assertEquals([true, false], [
            array_key_exists("/animals", $routes) && array_key_exists("post", $routes["/animals"]),
            array_key_exists("/animals", $routes) && array_key_exists("get", $routes["/animals"])
        ]);
    }
}