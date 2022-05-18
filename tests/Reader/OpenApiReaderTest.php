<?php
namespace Terrazza\Component\HttpRouting\OpenApi\Tests\Reader;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Terrazza\Component\HttpRouting\OpenApi\Tests\_Mocks\Helper;

class OpenApiReaderTest extends TestCase {

    function testFailureFileExists() {
        $this->expectException(RuntimeException::class);
        Helper::getOpenApiReader("unknown.file");
    }

    function testFailureFileYamlFailure() {
        $this->expectException(RuntimeException::class);
        Helper::getOpenApiReader(__FILE__);
    }

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


    /**
     * GetContentByRef
     */
    function testGetContentByRefRootNodeFailure() {
        $reader                                     = Helper::getOpenApiReader();
        $this->expectException(RuntimeException::class);
        Helper::invokeMethod($reader, "getContentByRef", ["#me"]);
    }

    function testGetContentByRefNodeFailure() {
        $reader                                     = Helper::getOpenApiReader();
        Helper::setPropertyValue($reader, "content", [
            "schema" => ["me"]
        ]);
        $this->expectException(RuntimeException::class);
        Helper::invokeMethod($reader, "getContentByRef", ["#/schema/you"]);
    }

    function testGetContentByRefNodeExistsButNull() {
        $reader                                     = Helper::getOpenApiReader( null, true);
        Helper::setPropertyValue($reader, "content", [
            "schema" => ["me" => null]
        ]);
        $this->expectException(RuntimeException::class);
        Helper::invokeMethod($reader, "getContentByRef", ["#/schema/me"]);
    }

    function testGetContentByRefNodeFound() {
        $reader                                     = Helper::getOpenApiReader( null, true);
        Helper::setPropertyValue($reader, "content", [
            "schema" => ["me" => []]
        ]);
        $this->assertIsArray(Helper::invokeMethod($reader, "getContentByRef", ["#/schema/me"]));
    }

    /**
     * mergePathParameters
     */
    function testSplitPathParametersSchemaNodeMissing() {
        $reader                                     = Helper::getOpenApiReader(Helper::yamlFailureFileName);
        $this->expectException(RuntimeException::class);
        Helper::invokeMethod($reader, "splitPathParameters", [
            [
                [
                    "in"    => "query",
                    "name"  => "test"
                ]
            ]
        , []]);
    }

    function testSplitPathParametersNotAnArray() {
        $reader                                     = Helper::getOpenApiReader(Helper::yamlFailureFileName);
        $this->expectException(RuntimeException::class);
        Helper::invokeMethod($reader, "splitPathParameters", [
            [
                "in"    => "query",
                "name"  => "test"
            ]
            , []]);
    }

    function testSplitPathParametersSchemeNotAnArray() {
        $reader                                     = Helper::getOpenApiReader(Helper::yamlFailureFileName);
        $this->expectException(RuntimeException::class);
        Helper::invokeMethod($reader, "splitPathParameters", [
            [
                [
                    "in"    => "query",
                    "name"  => "test",
                    "schema" => "schema"
                ]
            ], []]);
    }

    function testSplitPathParametersSuccessful() {
        $reader                                     = Helper::getOpenApiReader(Helper::yamlFailureFileName);
        $parameters                                 = Helper::invokeMethod($reader, "splitPathParameters", [
            [
                [
                    "in"    => $type = "query",
                    "name"  => $name = "test",
                    "schema" => $schema = ["type" => "string"]
                ]
            ], []]);
        $this->assertEquals([
            $type => [
                $name => $schema
            ]
        ], $parameters);
    }
}