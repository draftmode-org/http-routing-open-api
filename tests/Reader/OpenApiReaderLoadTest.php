<?php
namespace Terrazza\Component\HttpRouting\OpenApi\Tests\Reader;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Terrazza\Component\HttpRouting\OpenApi\Tests\_Mocks\Helper;

class OpenApiReaderLoadTest extends TestCase {

    /**
     * load
     */
    function testSuccessfulYaml() {
        Helper::getOpenApiReader(Helper::yamlFileName);
        $this->assertTrue(true);
    }

    function testFailureFileExists() {
        $this->expectException(RuntimeException::class);
        Helper::getOpenApiReader("unknown.file");
    }

    function testFailureFileYamlFailure() {
        $this->expectException(RuntimeException::class);
        Helper::getOpenApiReader(__FILE__);
    }
}