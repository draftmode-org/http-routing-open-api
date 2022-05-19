<?php
namespace Terrazza\Component\HttpRouting\OpenApi\Tests\Router;
use PHPUnit\Framework\TestCase;
use Terrazza\Component\Http\Message\Uri\Uri;
use Terrazza\Component\Http\Request\HttpServerRequest;
use Terrazza\Component\HttpRouting\OpenApi\OpenApiReader;
use Terrazza\Component\HttpRouting\OpenApi\OpenApiRouter;
use Terrazza\Dev\Logger\Logger;

class OpenApiRouterTest extends TestCase {
    CONST routingFileName   = "tests/_Examples/api.yaml";
    CONST baseUri           = "https://test.terrazza.io";

    function testFindRouteWithOutValidation() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(false);
        $reader             = new OpenApiReader($logger, self::routingFileName);
        $router             = new OpenApiRouter($reader, $logger);
        $serverRequest      = new HttpServerRequest("GET", new Uri(self::baseUri."/payments/12345"));
        $this->assertEquals([
            false,
            true
        ],[
            is_null($router->getRoute($serverRequest)),
            is_null($router->getRoute($serverRequest->withUri(new Uri(self::baseUri."/jobs"))))
        ]);
    }
}