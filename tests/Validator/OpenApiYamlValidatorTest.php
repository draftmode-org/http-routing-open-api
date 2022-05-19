<?php
namespace Terrazza\Component\HttpRouting\OpenApi\Tests\Validator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Terrazza\Component\HttpRouting\OpenApi\OpenApiYamlValidator;
use Terrazza\Component\HttpRouting\OpenApi\OpenApiYamlValidatorInterface;
use Terrazza\Component\HttpRouting\OpenApi\Tests\_Mocks\Helper;
use Terrazza\Component\Validator\ObjectValueSchema;
use Terrazza\Dev\Logger\Logger;

class OpenApiYamlValidatorTest extends TestCase {
    private function getValidator($logType=null) : OpenApiYamlValidatorInterface {
        $logger             = (new Logger("OpenApiYamlValidator"))->createLogger($logType);
        return new OpenApiYamlValidator($logger);
    }

    function testSimpleSchemaBuild() {
        $validator          = $this->getValidator();
        $buildSchema        = Helper::invokeMethod($validator, "createValidatorSchema", [
            $property1Name = "id",
            ["type" => $property1Type = "string"]
        ]);
        $expectedSchema     = new ObjectValueSchema($property1Name, $property1Type);
        $this->assertEquals($expectedSchema, $buildSchema);
    }

    function testObjectSchemaBuild() {
        $validator          = $this->getValidator();
        $buildSchema        = Helper::invokeMethod($validator, "createValidatorSchema", [
            $property1Name = "id",
            ["type" => $property1Type = "object", "properties" => [
                $property2Name = "name" => [
                    "type" => $property2Type = "string"
                ]
            ]]
        ]);
        $expectedSchema     = new ObjectValueSchema($property1Name, $property1Type);
        $expectedSchema->setChildSchemas(new ObjectValueSchema($property2Name, $property2Type));
        $this->assertEquals($expectedSchema, $buildSchema);
    }

    function testOneOfSchemaBuild() {
        $validator          = $this->getValidator();
        $buildSchema        = Helper::invokeMethod($validator, "createValidatorSchema", [
            $property1Name = "requestBody",
            ["type" => $property1Type = "oneOf", "properties" => [
                0 => [
                    "type" => $property2Type = "string"
                ]
            ]]
        ]);
        $expectedSchema     = new ObjectValueSchema($property1Name, $property1Type);
        $expectedSchema->setChildSchemas(new ObjectValueSchema(0, $property2Type));
        $this->assertEquals($expectedSchema, $buildSchema);
    }

    function testOneOfValidateSuccessful() {
        $validator          = $this->getValidator();
        $validator->validate("requestBody", "hallo", ["type" => "oneOf", "properties" => [
            0 => [
                "type" => "integer"
            ],
            1 => [
                "type" => "string"
            ]
        ]]);
        $this->assertTrue(true);
    }

    function testOneOfValidateFailure() {
        $validator          = $this->getValidator();
        $this->expectException(InvalidArgumentException::class);
        $validator->validate("requestBody", ["hallo"], ["type" => "oneOf", "properties" => [
            0 => [
                "type" => "integer"
            ],
            1 => [
                "type" => "string"
            ]
        ]]);
    }
}