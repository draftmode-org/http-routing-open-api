<?php
namespace Terrazza\Component\HttpRouting\OpenApi;

use Psr\Log\LoggerInterface;
use Terrazza\Component\Validator\ObjectValueValidator;
use Terrazza\Component\Validator\ObjectValueSchema;

class OpenApiYamlValidator implements OpenApiYamlValidatorInterface {
    private ObjectValueValidator $validator;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger) {
        $this->validator                            = new ObjectValueValidator($logger);
        $this->logger                               = $logger;
    }

    /**
     * @param string $schemaName
     * @param mixed|null $content
     * @param array $properties
     */
    public function validate(string $schemaName, $content, array $properties) : void {
        $contentSchema                              = $this->createValidatorSchema($schemaName, $properties);
        $this->validator->validate($content, $contentSchema);
    }

    /**
     * @param string $parameterName
     * @param array $properties
     * @return ObjectValueSchema
     */
    private function createValidatorSchema(string $parameterName, array $properties) : ObjectValueSchema {
        $this->logger->debug("create schema for $parameterName", $properties);
        $schema                                     = (new ObjectValueSchema($parameterName, $properties["type"]));
        $schema
            ->setRequired($properties["required"] ?? false)
            ->setNullable($properties["nullable"] ?? false)

            ->setPatterns($properties["patterns"] ?? null)
            ->setFormat($properties["format"] ?? null)
            ->setMinLength($properties["minLength"] ?? null)
            ->setMaxLength($properties["maxLength"] ?? null)
            ->setMinItems($properties["minItems"] ?? null)
            ->setMaxItems($properties["maxItems"] ?? null)
            ->setMinRange($properties["minimum"] ?? null)
            ->setMaxRange($properties["maximum"] ?? null)
            ->setMultipleOf($properties["multipleOf"] ?? null)
            ->setEnum($properties["enum"] ?? null)
        ;
        if (array_key_exists("properties", $properties)) {
            $childSchema                            = [];
            foreach ($properties["properties"] as $childName => $childProperties) {
                $childSchema[]                      = $this->createValidatorSchema($childName, $childProperties);
            }
            $schema->setChildSchemas(...$childSchema);
        }
        return $schema;
    }
}