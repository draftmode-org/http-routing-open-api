<?php
namespace Terrazza\Component\HttpRouting\OpenApi\Tests\Reader;
use PHPUnit\Framework\TestCase;
use Terrazza\Component\HttpRouting\OpenApi\Tests\_Mocks\Helper;

class OpenApiReaderGetPathParametersTest extends TestCase {

    /**
     * getPathParameters
     */
    function testGetPathParametersPathsEmpty() {
        $reader                                     = Helper::getOpenApiReader();
        $this->assertNull(Helper::invokeMethod($reader, "getPathParameters", ["/payments", "get"]));
    }

    function testGetPathParametersUriNotFound() {
        $reader                                     = Helper::getOpenApiReader();
        Helper::setPropertyValue($reader, "content", [
            "paths" => []
        ]);
        $this->assertNull(Helper::invokeMethod($reader, "getPathParameters", ["/payments", "get"]));
    }

    function testGetPathParametersFound() {
        $reader                                     = Helper::getOpenApiReader();
        Helper::setPropertyValue($reader, "content", [
            "paths" => [
                "/payments" => [
                    "parameters" => [],
                    "get" => [
                        "parameters" => []
                    ],
                    "post" => "",
                ]
            ]
        ]);
        $this->assertEquals([
            null,
            null,
            null,
            []
        ],[
            Helper::invokeMethod($reader, "getPathParameters", ["/animals", "post"]), // uri not found
            Helper::invokeMethod($reader, "getPathParameters", ["/payments", "post"]), // method not found
            Helper::invokeMethod($reader, "getPathParameters", ["/payments", "post"]), // method found, but properties not an array
            Helper::invokeMethod($reader, "getPathParameters", ["/payments", "get"]) // method found
        ]);
    }
}