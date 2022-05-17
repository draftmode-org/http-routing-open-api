<?php
namespace Terrazza\Component\HttpRouting\OpenApi\Tests;
use DateTime;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Terrazza\Component\Http\Message\Uri\Uri;
use Terrazza\Component\Http\Request\HttpServerRequest;
use Terrazza\Component\HttpRouting\HttpRoute;
use Terrazza\Component\HttpRouting\OpenApi\OpenApiRouteValidator;
use Terrazza\Dev\Logger\Logger;

class OpenApiRouteValidatorXest extends TestCase {
    CONST routingFileName   = "tests/_Examples/api.yaml";
    CONST baseUri           = "https://test.terrazza.io";

    /*
     * GET PAYMENT tests
     */
    function testGetPaymentSuccessful() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(false);
        $validator          = new OpenApiRouteValidator(self::routingFileName, $logger);
        $path               = "/payments/12345";
        $serverRequest      = new HttpServerRequest("GET", new Uri(self::baseUri.$path));
        $httpRoute          = new HttpRoute("/payments/{paymentId}", "get", "requestHandlerClass");
        $validator->validate($httpRoute, $serverRequest);
        $this->assertTrue(true);
    }

    function testGetPaymentFailurePathParam() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(false);
        $validator          = new OpenApiRouteValidator(self::routingFileName, $logger);
        $path               = "/payments/1234509";
        $serverRequest      = new HttpServerRequest("GET", new Uri(self::baseUri.$path));
        $httpRoute          = new HttpRoute("/payments/{paymentId}", "get", "requestHandlerClass");
        $this->expectException(InvalidArgumentException::class);
        $validator->validate($httpRoute, $serverRequest);
    }

    /*
     * GET PAYMENTS tests
     */
    function testGetPaymentsSuccessful() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(false);
        $validator          = new OpenApiRouteValidator(self::routingFileName, $logger);
        $path               = "/payments";
        $serverRequest      = (new HttpServerRequest("GET", new Uri(self::baseUri.$path)))
            ->withQueryParams(["paymentFrom" => (new DateTime)->format("Y-m-d")]);
        $httpRoute          = new HttpRoute("/payments", "get", "requestHandlerClass");
        $validator->validate($httpRoute, $serverRequest);
        $this->assertTrue(true);
    }

    function testGetPaymentsSuccessfulEnum() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(false);
        $validator          = new OpenApiRouteValidator(self::routingFileName, $logger);
        $path               = "/payments";
        $serverRequest      = (new HttpServerRequest("GET", new Uri(self::baseUri.$path)))
            ->withQueryParams([
                "paymentFrom" => (new DateTime)->format("Y-m-d"),
                "paymentState" => 1
            ]);
        $httpRoute          = new HttpRoute("/payments", "get", "requestHandlerClass");
        $validator->validate($httpRoute, $serverRequest);
        $this->assertTrue(true);
    }

    function testGetPaymentsFailureEnum() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(false);
        $validator          = new OpenApiRouteValidator(self::routingFileName, $logger);
        $path               = "/payments";
        $serverRequest      = (new HttpServerRequest("GET", new Uri(self::baseUri.$path)))
            ->withQueryParams([
                "paymentFrom" => (new DateTime)->format("Y-m-d"),
                "paymentState" => 12
            ]);
        $httpRoute          = new HttpRoute("/payments", "get", "requestHandlerClass");
        $this->expectException(InvalidArgumentException::class);
        $validator->validate($httpRoute, $serverRequest);
    }

    function testGetPaymentsFailureQueryParamMissing() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(false);
        $validator          = new OpenApiRouteValidator(self::routingFileName, $logger);
        $path               = "/payments";
        $serverRequest      = new HttpServerRequest("GET", new Uri(self::baseUri.$path));
        // queryParam paymentFrom required, missing
        $httpRoute          = new HttpRoute("/payments", "get", "requestHandlerClass");
        $this->expectException(InvalidArgumentException::class);
        $validator->validate($httpRoute, $serverRequest);
    }

    /*
     * POST PAYMENT tests
     */
    function testPostSuccessful() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(false);
        $validator          = new OpenApiRouteValidator(self::routingFileName, $logger);
        $path               = "/payments";
        $serverRequest      = (new HttpServerRequest("POST", new Uri(self::baseUri.$path)))
            ->withBody(json_encode(["paymentDate" => "2022-01-01"]));
        $httpRoute          = new HttpRoute("/payments", "post", "requestHandlerClass");
        $validator->validate($httpRoute, $serverRequest);
        $this->assertTrue(true);
    }

    function testPostSuccessfulOneOf() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(false);
        $validator          = new OpenApiRouteValidator(self::routingFileName, $logger);
        $path               = "/animals";
        $serverRequest      = (new HttpServerRequest("POST", new Uri(self::baseUri.$path)))
            ->withBody(json_encode(["dogName" => "myName"]));
        $httpRoute          = new HttpRoute("/animals", "post", "requestHandlerClass");
        $validator->validate($httpRoute, $serverRequest);
        $this->assertTrue(true);
    }

    function xtestPostFailureNoOneOfMatches() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(true);
        $validator          = new OpenApiRouteValidator(self::routingFileName, $logger);
        $path               = "/animals";
        $serverRequest      = (new HttpServerRequest("POST", new Uri(self::baseUri.$path)))
            ->withBody(json_encode(["name" => "myName"]));
        $httpRoute          = new HttpRoute("/animals", "post", "requestHandlerClass");
        $validator->validate($httpRoute, $serverRequest);
    }

    function testPostFailureNoContent() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(false);
        $validator          = new OpenApiRouteValidator(self::routingFileName, $logger);
        $path               = "/payments";
        $serverRequest      = (new HttpServerRequest("post", new Uri(self::baseUri.$path)));
        $httpRoute          = new HttpRoute("/payments", "post", "requestHandlerClass");
        $this->expectException(InvalidArgumentException::class);
        $validator->validate($httpRoute, $serverRequest);
    }

    function testPostFailureContentInvalid() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(false);
        $validator          = new OpenApiRouteValidator(self::routingFileName, $logger);
        $path               = "/payments";
        $serverRequest      = (new HttpServerRequest("POST", new Uri(self::baseUri.$path)))
            ->withBody("plain text");
        $httpRoute          = new HttpRoute("/payments", "post", "requestHandlerClass");
        $this->expectException(InvalidArgumentException::class);
        $validator->validate($httpRoute, $serverRequest);
    }

    function testPostFailureContentPropertyInvalid() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(false);
        $validator          = new OpenApiRouteValidator(self::routingFileName, $logger);
        $path               = "/payments";
        $serverRequest      = (new HttpServerRequest("POST", new Uri(self::baseUri.$path)))
            ->withBody(json_encode(["paymentDate" => "2022-31-01"]));
        $httpRoute          = new HttpRoute("/payments", "post", "requestHandlerClass");
        $this->expectException(InvalidArgumentException::class);
        $validator->validate($httpRoute, $serverRequest);
    }

}