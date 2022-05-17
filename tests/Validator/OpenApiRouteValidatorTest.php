<?php
namespace Terrazza\Component\HttpRouting\OpenApi\Tests\Validator;
use DateTime;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Terrazza\Component\Http\Message\Uri\Uri;
use Terrazza\Component\Http\Request\HttpServerRequest;
use Terrazza\Component\HttpRouting\HttpRoute;
use Terrazza\Component\HttpRouting\HttpRoutingValidatorInterface;
use Terrazza\Component\HttpRouting\OpenApi\OpenApiRouteValidator;
use Terrazza\Dev\Logger\Logger;

class OpenApiRouteValidatorTest extends TestCase {
    CONST routingFileName   = "tests/_Examples/api.yaml";
    CONST baseUri           = "https://test.terrazza.io";

    private function getValidator(string $yamlFile, $logType=null) : HttpRoutingValidatorInterface {
        $logger             = (new Logger("OpenApiRouteValidator"))->createLogger($logType);
        return new OpenApiRouteValidator($yamlFile, $logger);
    }
    /*
     * GET PAYMENT tests
     */
    function testGetPaymentSuccessful() {
        $validator          = $this->getValidator(self::routingFileName, false);
        $path               = "/payments/12345";
        $serverRequest      = new HttpServerRequest("GET", new Uri(self::baseUri.$path));
        $httpRoute          = new HttpRoute("/payments/{paymentId}", "get", "requestHandlerClass");
        $validator->validate($httpRoute, $serverRequest);
        $this->assertTrue(true);
    }

    function testGetPaymentFailurePathParam() {
        $validator          = $this->getValidator(self::routingFileName, false);
        $path               = "/payments/1234509";
        $serverRequest      = new HttpServerRequest("GET", new Uri(self::baseUri.$path));
        $httpRoute          = new HttpRoute("/payments/{PaymentId}", "get", "requestHandlerClass");
        $this->expectException(InvalidArgumentException::class);
        $validator->validate($httpRoute, $serverRequest);
    }

    /*
     * GET PAYMENTS tests
     */
    function testGetPaymentsSuccessful() {
        $validator          = $this->getValidator(self::routingFileName, false);
        $path               = "/payments";
        $serverRequest      = (new HttpServerRequest("GET", new Uri(self::baseUri.$path)))
            ->withQueryParams(["paymentFrom" => (new DateTime)->format("Y-m-d")]);
        $httpRoute          = new HttpRoute("/payments", "get", "requestHandlerClass");
        $validator->validate($httpRoute, $serverRequest);
        $this->assertTrue(true);
    }

    function testGetPaymentsSuccessfulEnum() {
        $validator          = $this->getValidator(self::routingFileName, false);
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
        $validator          = $this->getValidator(self::routingFileName, false);
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
        $validator          = $this->getValidator(self::routingFileName, false);
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
        $validator          = $this->getValidator(self::routingFileName, false);
        $path               = "/payments";
        $serverRequest      = (new HttpServerRequest("POST", new Uri(self::baseUri.$path)))
            ->withBody(json_encode(["PaymentDate" => "2022-01-01"]));
        $httpRoute          = new HttpRoute("/payments", "post", "requestHandlerClass");
        $validator->validate($httpRoute, $serverRequest);
        $this->assertTrue(true);
    }

    function testPostSuccessfulOneOf() {
        $validator          = $this->getValidator(self::routingFileName, false);
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