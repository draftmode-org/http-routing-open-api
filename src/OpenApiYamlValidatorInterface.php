<?php
namespace Terrazza\Component\HttpRouting\OpenApi;

interface OpenApiYamlValidatorInterface {
    /**
     * @param string $schemaName
     * @param mixed|null $content
     * @param array $properties
     */
    public function validate(string $schemaName, $content, array $properties) : void;
}