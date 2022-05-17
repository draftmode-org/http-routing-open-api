<?php
namespace Terrazza\Component\HttpRouting\OpenApi\Tests;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Terrazza\Component\HttpRouting\OpenApi\OpenApiReader;
use Terrazza\Component\HttpRouting\OpenApi\OpenApiReaderInterface;
use Terrazza\Dev\Logger\Logger;

class OpenApiReaderTest extends TestCase {
    CONST yamlFileName          = "tests/_Examples/api.yaml";
    CONST yamlFailureFileName   = "tests/_Examples/apiFailure.yaml";

    private function getReader(string $yamlFile=null, $logType=null) : OpenApiReaderInterface {
        $logger             = (new Logger("OpenApiRouter"))->createLogger($logType);
        $reader             = new OpenApiReader($logger);
        return $reader->load($yamlFile);
    }

    function testFailureFileExists() {
        $this->expectException(RuntimeException::class);
        $this->getReader("unknown.file");
    }

    function testFailureFileYamlFailure() {
        $this->expectException(RuntimeException::class);
        $this->getReader(__FILE__);
    }

    function testFailureGetRequestBodyContentsEmptyNode() {
        $reader             = $this->getReader(self::yamlFailureFileName);
        $this->expectException(RuntimeException::class);
        $reader->getRequestBodyContents("/payments", "patch");
    }
    function testGetRequestBodyContents() {
        $reader             = $this->getReader(self::yamlFileName);
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

    function testFailureGetRequestBodyContentsRouteNotFound() {
        $reader             = $this->getReader(self::yamlFileName);
        $this->expectException(RuntimeException::class);
        $reader->getRequestBodyContents("/payments", "change");
    }

    function testFailureGetRequestBodyContentsContentMissing() {
        $reader             = $this->getReader(self::yamlFailureFileName);
        $this->expectException(RuntimeException::class);
        $reader->getRequestBodyContents("/payments", "put");
    }

    function testFailureGetParameterParamsNodeSchemaMissing() {
        $reader             = $this->getReader(self::yamlFailureFileName);
        $this->expectException(RuntimeException::class);
        $reader->getParameterParams("/animals", "get", "query");
    }

    function testGetRequestBodyParamsOneOf() {
        $reader             = $this->getReader(self::yamlFileName);
        $contents           = $reader->getRequestBodyContents("/animals", "post");
        $this->assertIsArray($reader->getRequestBodyParams($contents, "application/json"));
    }

    function testFailureGetRequestBodyParamsContentType() {
        $reader             = $this->getReader(self::yamlFailureFileName);
        $this->expectException(InvalidArgumentException::class);
        $reader->getRequestBodyParams([], "application/json");
    }

    function testFailureGetRequestBodyParamsNodeSchemaMissing() {
        $reader             = $this->getReader(self::yamlFailureFileName);
        $this->expectException(RuntimeException::class);
        $reader->getRequestBodyParams(["application/json" => []], "application/json");
    }

    function testFailureGetRequestBodyParamsNodeTypeMissing() {
        $reader             = $this->getReader(self::yamlFailureFileName);
        $this->expectException(RuntimeException::class);
        $reader->getRequestBodyParams(["application/json" => ["schema" => []]], "application/json");
    }

    function testFailureGetContentByRefNodeNotFound() {
        $reader             = $this->getReader(self::yamlFailureFileName);
        $this->expectException(RuntimeException::class);
        $reader->getParameterParams("/animals", "patch", "query");
    }

    function testGetRoutes() {
        $reader             = $this->getReader(self::yamlFileName, false);
        $routes             = $reader->getRoutes();
        $this->assertEquals([true, false], [
            array_key_exists("/animals", $routes) && array_key_exists("post", $routes["/animals"]),
            array_key_exists("/animals", $routes) && array_key_exists("get", $routes["/animals"])
        ]);
    }

    function testGetParameterParamPath() {
        $reader             = $this->getReader(self::yamlFileName, false);
        $this->assertEquals([
            [
                "type" => "object",
                "properties" => [
                    "id" => [
                        "type" => "string",
                        "maxLength" => 5
                    ],
                    "paymentFrom" => [
                        "type" => "string",
                        "format" => "date"
                    ]
                ]
            ],
            null,
            null,
            null,
        ],[
            $reader->getParameterParams("/payments/{paymentId}", "get", "query"), // uri, method, paramType found
            $reader->getParameterParams("/payments/{paymentId}", "delete", "query"), // uri found, method not found
            $reader->getParameterParams("/payments/{paymentId}", "get", "unknown"), // uri, method found, paramType not found
            $reader->getParameterParams("/unknown", "get", "unknown"), // uri not found
        ]);
    }
}