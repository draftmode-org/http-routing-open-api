<?php
namespace Terrazza\Component\HttpRouting\OpenApi\Tests\Reader;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Terrazza\Component\HttpRouting\OpenApi\Tests\_Mocks\Helper;

class OpenApiReaderGetEncodedPropertyTest extends TestCase {
    /**
     * getEncodedProperty
     */
    function testGetEncodedPropertyWithTypeSuccessful() {
        $reader                                     = Helper::getOpenApiReader();
        $encodedProperty                            = Helper::invokeMethod($reader, "getEncodedProperty", [
            "name",
            $property = [
                "type" => "string",
                "required" => true
            ]
        ]);
        $this->assertEquals($property,$encodedProperty);
    }

    function testGetEncodedPropertyWithSchemaSuccessful() {
        $reader                                     = Helper::getOpenApiReader();
        Helper::setPropertyValue($reader, "content", [
            "schema" => ["ref" => [
                "type" => "string"
            ]]
        ]);
        $encodedProperty                            = Helper::invokeMethod($reader, "getEncodedProperty", [
            "name",
            [
                "\$ref" => "#/schema/ref",
                "required" => true // move required into property
            ]
        ]);
        $this->assertEquals([
            "type" => "string",
            "required" => true
        ],$encodedProperty);
    }

    function testGetEncodedPropertyWithRefAndPropertiesSuccessful() {
        $reader                                     = Helper::getOpenApiReader();
        Helper::setPropertyValue($reader, "content", [
            "schema" => ["ref" => [
                "type" => "object",
                "required" => ["fieldB"], // move required into property, fieldB
                "properties" => [
                    "fieldA" => [
                        "type" => "string"
                    ],
                    "fieldB" => [
                        "type" => "string"
                    ]
                ]
            ]]
        ]);
        $encodedProperty                            = Helper::invokeMethod($reader, "getEncodedProperty", [
            "name",
            [
                "\$ref" => "#/schema/ref"
            ]
        ]);
        $this->assertEquals([
            "type" => "object",
            "properties" => [
                "fieldA" => [
                    "type" => "string"
                ],
                "fieldB" => [
                    "type" => "string",
                    "required" => true
                ]
            ]
        ],$encodedProperty);
    }

    function testGetEncodedPropertyMultipleOfSuccessful() {
        $reader                                     = Helper::getOpenApiReader();
        Helper::setPropertyValue($reader, "content", [
            "schema" => [
                "ref1" => $property1 = [
                    "type" => "object",
                    "properties" => [
                        "fieldA" => [
                            "type" => "string"
                        ],
                        "fieldB" => [
                            "type" => "string"
                        ]
                    ]
                ],
                "ref2" => $property2 = [
                    "type" => "string"
                ]
            ]
        ]);
        $encodedProperty                            = Helper::invokeMethod($reader, "getEncodedProperty", [
            "name",
            [
                "oneOf" => [
                    ["\$ref" => "#/schema/ref1"],
                    ["\$ref" => "#/schema/ref2"],
                ]
            ]
        ]);
        $this->assertEquals([
            "type" => "oneOf",
            "properties" => [
                0 => $property1,
                1 => $property2
            ]
        ],$encodedProperty);
    }

    function testGetEncodedPropertyMultipleOfNotArray() {
        $reader                                     = Helper::getOpenApiReader();
        $this->expectException(RuntimeException::class);
        Helper::invokeMethod($reader, "getEncodedProperty", [
            "name",
            [
                "oneOf" => "yes"
            ]
        ]);
    }

    function testGetEncodedPropertyMultipleOfChildNotArray() {
        $reader                                     = Helper::getOpenApiReader();
        $this->expectException(RuntimeException::class);
        Helper::invokeMethod($reader, "getEncodedProperty", [
            "name",
            [
                "oneOf" => ["yes"]
            ]
        ]);
    }

    function testGetEncodedPropertyWithPropertiesNotArray() {
        $reader                                     = Helper::getOpenApiReader(null, true);
        $this->expectException(RuntimeException::class);
        Helper::invokeMethod($reader, "getEncodedProperty", [
            "name",
            [
                "type" => "object",
                "properties" => ""
            ]
        ]);
    }

    function testGetEncodedPropertyWithPropertiesChildPropertyNotArray() {
        $reader                                     = Helper::getOpenApiReader();
        $this->expectException(RuntimeException::class);
        Helper::invokeMethod($reader, "getEncodedProperty", [
            "name",
            [
                "type" => "object",
                "properties" => ["12"]
            ]
        ]);
    }

    function testGetEncodedPropertyTypeMissing() {
        $reader                                     = Helper::getOpenApiReader();
        $this->expectException(RuntimeException::class);
        Helper::invokeMethod($reader, "getEncodedProperty", [
            "name",
            []
        ]);
    }

    /**
     * getEncodedProperties
     */
    function testGetEncodedPropertiesWithSchemaSuccessful() {
        $reader                                     = Helper::getOpenApiReader();
        Helper::setPropertyValue($reader, "content", [
            "schema" => ["ref" => $properties = [
                "type" => "object",
                "properties" => [
                    "fieldA" => [
                        "type" => "string"
                    ],
                    "fieldB" => [
                        "type" => "string"
                    ]
                ]
            ]]
        ]);
        $encodedProperties                          = Helper::invokeMethod($reader, "getEncodedProperties", [
            ["name" => [
                "\$ref" => "#/schema/ref"
            ]]
        ]);
        $this->assertEquals([
            "name" => $properties
        ],$encodedProperties);
    }
}