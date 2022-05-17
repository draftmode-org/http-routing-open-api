<?php
namespace Terrazza\Component\HttpRouting\OpenApi\Tests\Reader;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Terrazza\Component\HttpRouting\OpenApi\Tests\_Mocks\OpenApiReader;

class OpenApiReaderParameterTest extends TestCase {

    function testGetParameterParamPath() {
        $reader             = OpenApiReader::getReader(OpenApiReader::yamlFileName, false);
        $this->assertEquals([
            [
                "type" => "object",
                "properties" => [
                    "id" => [
                        "type" => "string",
                        "maxLength" => 5,
                        //"required" => true,
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
            $reader->getParameterParams("/payments/{PaymentId}", "get", "query"), // uri, method, paramType found
            $reader->getParameterParams("/payments/{PaymentId}", "delete", "query"), // uri found, method not found
            $reader->getParameterParams("/payments/{PaymentId}", "get", "unknown"), // uri, method found, paramType not found
            $reader->getParameterParams("/unknown", "get", "unknown"), // uri not found
        ]);
    }
}