<?php
namespace Terrazza\Component\HttpRouting\OpenApi\Tests\Reader;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Terrazza\Component\HttpRouting\OpenApi\Tests\_Mocks\Helper;

class OpenApiReaderRequestBodyTest extends TestCase {

    function testGetRequestBodyContents() {
        $reader             = Helper::getOpenApiReader(Helper::yamlFileName);
        $this->assertEquals([
            true,
            false, // method has no requestBody not found
            false, // uri not found
        ],[
            !is_null($reader->getRequestBodyContents("/payments", "post")),
            !is_null($reader->getRequestBodyContents("/payments", "get")),
            !is_null($reader->getRequestBodyContents("/unknown", "delete")),
        ]);
    }

    function testRequestBodyContent() {
        $reader             = Helper::getOpenApiReader(Helper::yamlFileName);
        $contents           = $reader->getRequestBodyContents("/payments", "post");
        $content            = $reader->getRequestBodyParams($contents, "application/json");
        $this->assertEquals([
            "type" => "object",
            "properties" => [
                "PaymentDate" => [
                    "type" => "string",
                    "format" => "date",
                    "required" => true
                ],
                "PaymentState" => [
                    "type" => "number",
                    "enum" => [1,2,3],
                ],
                "Customer" => [
                    "type" => "object",
                    "properties" => [
                        "firstName" => [
                            "type" => "string",
                            "maxLength" => 10
                        ],
                    ]
                ],
            ]
        ], $content);
    }

    function testGetRequestBodyParamsOneOf() {
        $reader             = Helper::getOpenApiReader(Helper::yamlFileName);
        $contents           = $reader->getRequestBodyContents("/animals", "post");
        $this->assertIsArray($reader->getRequestBodyParams($contents, "application/json"));
    }

    function testFailureGetRequestBodyContentsRouteNotFound() {
        $reader             = Helper::getOpenApiReader(Helper::yamlFileName);
        $this->expectException(RuntimeException::class);
        $reader->getRequestBodyContents("/payments", "change");
    }

    function testFailureGetRequestBodyContentsContentMissing() {
        $reader             = Helper::getOpenApiReader(Helper::yamlFailureFileName);
        $this->expectException(RuntimeException::class);
        $reader->getRequestBodyContents("/payments", "put");
    }

    function testFailureGetRequestBodyParamsContentType() {
        $reader             = Helper::getOpenApiReader(Helper::yamlFailureFileName);
        $this->expectException(InvalidArgumentException::class);
        $reader->getRequestBodyParams([], "application/json");
    }

    function testFailureGetRequestBodyParamsNodeSchemaMissing() {
        $reader             = Helper::getOpenApiReader(Helper::yamlFailureFileName);
        $this->expectException(RuntimeException::class);
        $reader->getRequestBodyParams(["application/json" => []], "application/json");
    }

    function testFailureGetRequestBodyParamsNodeTypeMissing() {
        $reader             = Helper::getOpenApiReader(Helper::yamlFailureFileName);
        $this->expectException(RuntimeException::class);
        $reader->getRequestBodyParams(["application/json" => ["schema" => []]], "application/json");
    }

    function testFailureGetRequestBodyContentsEmptyNode() {
        $reader             = Helper::getOpenApiReader(Helper::yamlFailureFileName);
        $this->expectException(RuntimeException::class);
        $reader->getRequestBodyContents("/payments", "patch");
    }

}