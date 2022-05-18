<?php
namespace Terrazza\Component\HttpRouting\OpenApi\Tests\Reader;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Terrazza\Component\HttpRouting\OpenApi\Tests\_Mocks\Helper;

class OpenApiReaderSplitPathTest extends TestCase {
    /**
     * splitPathParameters
     */
    function testSplitPathParametersSchemaNodeMissing() {
        $reader                                     = Helper::getOpenApiReader();
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

    function testSplitPathParametersNotArray() {
        $reader                                     = Helper::getOpenApiReader();
        $this->expectException(RuntimeException::class);
        Helper::invokeMethod($reader, "splitPathParameters", [
            [
                "in"    => "query",
                "name"  => "test"
            ]
            , []]);
    }

    function testSplitPathParametersSchemeNotArray() {
        $reader                                     = Helper::getOpenApiReader();
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
        $reader                                     = Helper::getOpenApiReader();
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