<?php
namespace Terrazza\Component\HttpRouting\OpenApi\Tests\_Mocks;
use Terrazza\Component\HttpRouting\OpenApi\OpenApiReader;
use Terrazza\Component\HttpRouting\OpenApi\OpenApiReaderInterface;
use Terrazza\Dev\Logger\Logger;

class Helper {
    CONST yamlFileName          = "tests/_Examples/api.yaml";
    CONST yamlFailureFileName   = "tests/_Examples/apiFailure.yaml";

    public static function getOpenApiReader(string $yamlFile=null, $logType=null) : OpenApiReaderInterface {
        $logger             = (new Logger("OpenApiReader"))->createLogger($logType);
        $reader             = new OpenApiReader($logger);
        return $yamlFile ? $reader->load($yamlFile) : $reader;
    }

    public static function setPropertyValue(&$object, string $propertyName, $value) : void {
        $reflection = new \ReflectionClass(get_class($object));
        $property   = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    public static function invokeMethod(&$object, $methodName, array $parameters = array()) {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}