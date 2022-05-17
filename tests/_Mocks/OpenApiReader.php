<?php
namespace Terrazza\Component\HttpRouting\OpenApi\Tests\_Mocks;

use Terrazza\Component\HttpRouting\OpenApi\OpenApiReaderInterface;
use Terrazza\Dev\Logger\Logger;

class OpenApiReader {
    CONST yamlFileName          = "tests/_Examples/api.yaml";
    CONST yamlFailureFileName   = "tests/_Examples/apiFailure.yaml";

    public static function getReader(string $yamlFile=null, $logType=null) : OpenApiReaderInterface {
        $logger             = (new Logger("OpenApiRouter"))->createLogger($logType);
        $reader             = new \Terrazza\Component\HttpRouting\OpenApi\OpenApiReader($logger);
        return $reader->load($yamlFile);
    }
}