<?php
namespace Terrazza\Component\HttpRouting\OpenApi\Tests\Reader;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Terrazza\Component\HttpRouting\OpenApi\Tests\_Mocks\Helper;

class OpenApiReaderGetContentByRefTest extends TestCase {

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
        $reader                                     = Helper::getOpenApiReader();
        Helper::setPropertyValue($reader, "content", [
            "schema" => ["me" => null]
        ]);
        $this->expectException(RuntimeException::class);
        Helper::invokeMethod($reader, "getContentByRef", ["#/schema/me"]);
    }

    function testGetContentByRefNodeFound() {
        $reader                                     = Helper::getOpenApiReader();
        Helper::setPropertyValue($reader, "content", [
            "schema" => ["me" => []]
        ]);
        $this->assertIsArray(Helper::invokeMethod($reader, "getContentByRef", ["#/schema/me"]));
    }
}