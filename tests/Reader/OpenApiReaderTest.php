<?php
namespace Terrazza\Component\HttpRouting\OpenApi\Tests\Reader;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Terrazza\Component\HttpRouting\OpenApi\Tests\_Mocks\OpenApiReader;

class OpenApiReaderTest extends TestCase {

    function testFailureFileExists() {
        $this->expectException(RuntimeException::class);
        OpenApiReader::getReader("unknown.file");
    }

    function testFailureFileYamlFailure() {
        $this->expectException(RuntimeException::class);
        OpenApiReader::getReader(__FILE__);
    }

    function testGetContentRefNodeFailure() {
        $reader                                     = OpenApiReader::getReader(OpenApiReader::yamlFailureFileName);
        $this->expectException(RuntimeException::class);
        $reader->getParameterParams("/animals", "patch", "query");
    }

    function testGetContentByRefRootNodeFailure() {
        $reader                                     = OpenApiReader::getReader(OpenApiReader::yamlFailureFileName);
        $this->expectException(RuntimeException::class);
        $reader->getParameterParams("/animals", "put", "query");
    }

    function testMergePathParametersSchemaNodeMissing() {
        $reader                                     = OpenApiReader::getReader(OpenApiReader::yamlFailureFileName);
        $this->expectException(RuntimeException::class);
        $reader->getParameterParams("/animals", "delete", "query");
    }
}