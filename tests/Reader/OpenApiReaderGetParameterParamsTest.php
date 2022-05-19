<?php
namespace Terrazza\Component\HttpRouting\OpenApi\Tests\Reader;
use PHPUnit\Framework\TestCase;
use Terrazza\Component\HttpRouting\OpenApi\Tests\_Mocks\Helper;

class OpenApiReaderGetParameterParamsTest extends TestCase {

    function testGetParameterParamPath() {
        $reader             = Helper::getOpenApiReader();
        Helper::setPropertyValue($reader, "content", [
            "paths" => [
                "/payments/{PaymentId}" => [
                    "parameters" => [
                        [
                            "in" => "query",
                            "name" => "id",
                            "schema" => [
                                "type" => "string",
                                "maxLength" => 5,
                            ]
                        ],
                    ],
                    "get" => [
                        "parameters" => [
                            [
                                "in" => "query",
                                "name" => "paymentFrom",
                                "required" => true,
                                "schema" => [
                                    "type" => "string",
                                    "format" => "date"
                                ]
                            ]
                        ]
                    ],
                    "post" => "",
                ]
            ]
        ]);

        $this->assertEquals([
            [
                "type" => "object",
                "properties" => [
                    "id" => [
                        "type" => "string",
                        "maxLength" => 5,
                    ],
                    "paymentFrom" => [
                        "type" => "string",
                        "format" => "date",
                        "required" => true,
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