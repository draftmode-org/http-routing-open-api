<?php
namespace Terrazza\Component\HttpRouting\OpenApi\Tests\Reader;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Terrazza\Component\HttpRouting\OpenApi\Tests\_Mocks\Helper;

class OpenApiReaderRouteTest extends TestCase {

    function testGetRoutesParametersNotAnArray() {
        $reader                                     = Helper::getOpenApiReader();
        Helper::setPropertyValue($reader, "content", [
            "paths" => [
                "/payments" => [
                    "get" => ""
                ]
            ]
        ]);
        $this->expectException(RuntimeException::class);
        $reader->getRoutes();
    }

    function testGetRoutesOperationIdMissing() {
        $reader                                     = Helper::getOpenApiReader();
        Helper::setPropertyValue($reader, "content", [
            "paths" => [
                "/payments" => [
                    "get" => []
                ]
            ]
        ]);
        $this->expectException(RuntimeException::class);
        $reader->getRoutes();
    }

    function testGetRoutes() {
        $reader                                     = Helper::getOpenApiReader();
        Helper::setPropertyValue($reader, "content", [
            "paths" => [
                "/payments" => [
                    "parameters" => [], // in_array(skipMethods)
                    "get" => [
                        "operationId" => "payments_get",
                        "parameters" => []
                    ]
                ]
            ]
        ]);
        $routes                                     = $reader->getRoutes();
        $this->assertEquals(
            [
                true,
                false
            ],
            [
                array_key_exists("/payments", $routes) && array_key_exists("get", $routes["/payments"]),
                array_key_exists("/payments", $routes) && array_key_exists("delete", $routes["/payments"])
        ]);
    }
}