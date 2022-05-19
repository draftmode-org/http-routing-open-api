<?php
namespace Terrazza\Component\HttpRouting\OpenApi;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;

class OpenApiReader implements OpenApiReaderInterface {
    private LoggerInterface $logger;
    private ?array $content                         = null;
    private ?string $contentHash                    = null;
    private array $pathParameters                   = [];
    CONST multipleTypes = ["oneOf"];

    public function __construct(LoggerInterface $logger, ?string $yamlFileName=null) {
        $this->logger                               = $logger;
        if ($yamlFileName) {
            $this->load($yamlFileName);
        }
    }

    /**
     * @param string $yamlFileName
     * @return OpenApiReaderInterface
     */
    public function load(string $yamlFileName) : OpenApiReaderInterface {
        if (md5($yamlFileName) !== $this->contentHash) {
            $this->content                          = null;
            $this->pathParameters                   = [];
        }
        if (is_null($this->content)) {
            if (!file_exists($yamlFileName)) {
                throw new RuntimeException("yaml.file $yamlFileName does not exist");
            }
            $content                                = @yaml_parse_file($yamlFileName);
            if (is_array($content)) {
                $this->content                      = $content;
                $this->contentHash                  = md5($yamlFileName);
            } else {
                throw new RuntimeException("yaml.file $yamlFileName could no be parsed");
            }
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getRoutes() : array {
        $skipMethods                                = ["parameters"];
        $yaml                                       = $this->content ?? [];
        $paths                                      = [];
        foreach ($yaml["paths"] ?? [] as $uri => $methods) {
            foreach ($methods as $method => $properties) {
                if (!is_array($properties)) {
                    throw new RuntimeException("paths/$uri/$method expect array, given ".gettype($properties));
                }
                if (in_array($method, $skipMethods)) {
                    continue;
                }
                if (!array_key_exists($uri, $paths)) {
                    $paths[$uri]                    = [];
                }
                if (!array_key_exists("operationId", $properties)) {
                    throw new RuntimeException("property operationId in paths$uri/$method missing");
                }
                $paths[$uri][$method]               = $properties["operationId"];
            }
        }
        return $paths;
    }

    /**
     * @param string $routePath
     * @param string $routeMethod
     * @param string $parametersType
     * @return array|null
     */
    public function getParameterParams(string $routePath, string $routeMethod, string $parametersType) :?array {
        $this->logger->debug("search for params for uri $routePath, method $routeMethod and type $parametersType");
        if ($properties = $this->getPathParameters($routePath, $routeMethod)) {
            $this->logger->debug("params for uri $routePath and method $routeMethod found");
            if (array_key_exists($parametersType, $properties)) {
                $this->logger->debug("params for uri $routePath, method $routeMethod and type $parametersType found");
                return [
                    "type"                          => "object",
                    "properties"                    => $this->getEncodedProperties($properties[$parametersType])
                ];
            } else {
                $this->logger->debug("no params for uri $routePath, method $routeMethod and type $parametersType found");
            }
        } else {
            $this->logger->debug("no params for uri $routePath and method $routeMethod not found");
        }
        return null;
    }

    /**
     * @param array $content
     * @param string $contentType
     * @return array
     * @throws InvalidArgumentException
     */
    public function getRequestBodyParams(array $content, string $contentType): array {
        $this->logger->debug("getRequestBodyParams for $contentType");
        if (array_key_exists($contentType, $content)) {
            $content                                = $content[$contentType];
            if (array_key_exists("schema", $content)) {
                $content                            = $content["schema"];
                $this->logger->debug("getRequestBodyParams", $content);
                return $this->getEncodedProperty("requestBody", $content);
            } else {
                throw new RuntimeException("node schema for requestBody/$contentType does not exist");
            }
        } else {
            throw new InvalidArgumentException("requestBody Content-Type not accepted, given ".$contentType);
        }
    }

    /**
     * @param string $routePath
     * @param string $routeMethod
     * @return array|null
     */
    public function getRequestBodyContents(string $routePath, string $routeMethod) :?array {
        $this->logger->debug("getRequestBodyParams for $routePath:$routeMethod");
        $yaml                                       = $this->content ?? [];
        foreach ($yaml["paths"] ?? [] as $uri => $methods) {
            if ($uri === $routePath) {
                $this->logger->debug("...path $uri found");
                $requestBody                        = null;
                $methodFound                        = false;
                foreach ($methods as $method => $parameters) {
                    if ($method === $routeMethod) {
                        $methodFound                = true;
                        $this->logger->debug("...method $method found");
                        if (array_key_exists("requestBody", $parameters)) {
                            $requestBody                = $parameters["requestBody"];
                            $this->logger->debug("...requestBody found");
                        } else {
                            $this->logger->debug("...requestBody not found");
                        }
                        break;
                    }
                }
                if ($methodFound) {
                    if ($requestBody) {
                        while (array_key_exists("\$ref", $requestBody)) {
                            $propertyRef            = $requestBody["\$ref"];
                            $this->logger->debug("...getContentByRef for $propertyRef");
                            $requestBody            = $this->getContentByRef($propertyRef);
                        }
                        $contentNode                = "content";
                        if (!array_key_exists($contentNode, $requestBody)) {
                            throw new RuntimeException("node content for requestBody $routePath:$routeMethod does not exist");
                        }
                        $this->logger->debug("...requestBody & method found");
                        return $requestBody[$contentNode];
                    } else {
                        return null;
                    }
                } else {
                    throw new RuntimeException("...method $routeMethod for $routePath not found");
                }
            }
        }
        return null;
    }

    /**
     * @param string $routePath
     * @param string $routeMethod
     * @return array|null
     */
    private function getPathParameters(string $routePath, string $routeMethod) :?array {
        $this->logger->debug("search for params in uri $routePath and method $routeMethod");
        $pathKey                                    = "$routePath:$routeMethod";
        if (array_key_exists($pathKey, $this->pathParameters)) {
            $this->logger->debug("pathKey $pathKey already initialized");
            return $this->pathParameters[$pathKey];
        }
        $this->logger->debug("initialize pathKey $pathKey");
        $yaml                                       = $this->content ?? [];
        foreach ($yaml["paths"] ?? [] as $uri => $methods) {
            if ($uri === $routePath) {
                $this->logger->debug("uri $uri found");
                $pathParameters                     = null;
                $uriParametersFound                 = false;
                $methodParametersFound              = false;
                $uriParameter                       = [];
                $routeParameter                     = [];
                foreach ($methods as $method => $parameters) {
                    if (!is_array($parameters)) {
                        continue;
                    };
                    if ($method === "parameters") {
                        $this->logger->debug("method $method for uri $routePath found");
                        $uriParameter               = $parameters;
                        $uriParametersFound         = true;
                    }
                    if ($method === $routeMethod) {
                        $this->logger->debug("method $method for uri $routePath found");
                        $routeParameter             = $parameters["parameters"] ?? [];
                        $methodParametersFound      = true;
                    }
                    if ($uriParametersFound && $methodParametersFound) {
                        break;
                    }
                }
                if ($methodParametersFound) {
                    $pathParameters                 = $this->splitPathParameters($uriParameter, $routeParameter);
                }
                $this->pathParameters[$pathKey]     = $pathParameters;
                return $pathParameters;
            }
        }
        return null;
    }

    /**
     * @param array $uriParameter
     * @param array $methodParameter
     * @return array
     */
    private function splitPathParameters(array $uriParameter, array $methodParameter) : array {
        $this->logger->debug("merge parameters", ["uriParams" => $uriParameter, "methodParams" => $methodParameter]);
        $parameters                                 = array_filter(array_merge($uriParameter, $methodParameter));
        $response                                   = [];
        foreach ($parameters as $parameter) {
            if (!is_array($parameter)) {
                throw new RuntimeException("parameters items has to be an array, give ".gettype($parameter));
            }
            $type                                   = $parameter["in"] ?? "-";
            if (!array_key_exists($type, $response)) {
                $response[$type]                    = [];
            }
            $name                                   = $parameter["name"] ?? "-";
            $schemaNode                             = "schema";
            if (!array_key_exists($schemaNode, $parameter)) {
                throw new RuntimeException("node $schemaNode in parameters/$type for $name does not exist");
            }
            $schema                                 = $parameter[$schemaNode];
            if (array_key_exists("required", $parameter)) {
                $schema["required"]                 = $parameter["required"];
            }
            if (!is_array($schema)) {
                throw new RuntimeException("node parameters/$type/$name/schema expected array, given ".gettype($schema));
            }
            $response[$type][$name]                 = $schema;
        }
        return $response;
    }

    /**
     * @param array $properties
     * @return array
     */
    private function getEncodedProperties(array $properties) : array {
        foreach ($properties as $propertyName => $property) {
            $properties[$propertyName]              = $this->getEncodedProperty($propertyName, $property);
        }
        return $properties;
    }

    /**
     * @param string $propertyName
     * @param array $property
     * @param string|null $parentPropertyName
     * @return array
     */
    private function getEncodedProperty(string $propertyName, array $property, ?string $parentPropertyName=null) : array {
        $this->logger->debug("getEncodedProperty for $propertyName", $property);
        $propertyRequired                           = $property["required"] ?? null;
        while (array_key_exists("\$ref", $property)) {
            $propertyRef                            = $property["\$ref"];
            $this->logger->debug("...getContentByRef for $propertyRef");
            $property                               = $this->getContentByRef($propertyRef);
            $this->logger->debug("... ... found", $property);
            if (array_key_exists("required", $property)) {
                $propertyRequired                   = $property["required"];
            }
        }
        $fullPropertyName                           = $parentPropertyName ? $parentPropertyName . "." . $propertyName : $propertyName;
        foreach (self::multipleTypes as $multipleType) {
            if (array_key_exists($multipleType, $property)) {
                $this->logger->debug("multipleType $multipleType found in property", $property);
                $property["type"]                   = $multipleType;
                if (!is_array($property[$multipleType])) {
                    throw new RuntimeException("node $fullPropertyName/$multipleType expected array, given ".gettype($property[$multipleType]));
                }
                $property["properties"]             = [];
                foreach ($property[$multipleType] as $iChildProperty => $childProperty) {
                    if (!is_array($childProperty)) {
                        throw new RuntimeException("node $fullPropertyName/$multipleType.$iChildProperty expected array, given ".gettype($childProperty));
                    }
                    $property["properties"][]       = $childProperty;
                }
            }
        }
        if (array_key_exists("type", $property)) {
            if (array_key_exists("properties", $property)) {
                $childSchemas                       = [];
                $propertyProperties                 = $property["properties"];
                if (!is_array($propertyProperties)) {
                    throw new RuntimeException("node $fullPropertyName/properties expected array, given ".gettype($propertyProperties));
                }
                foreach ($property["properties"] as $childName => $childProperties) {
                    if (!is_array($childProperties)) {
                        throw new RuntimeException("nodes for $fullPropertyName/properties expected array, given ".gettype($childProperties));
                    }
                    $childSchema                    = $this->getEncodedProperty(
                        $childName, $childProperties, $fullPropertyName);
                    if (is_array($propertyRequired) &&
                        in_array($childName, $propertyRequired)) {
                        $childSchema["required"]    = true;
                    }
                    $childSchemas[$childName] = $childSchema;
                }
                $property["properties"]             = $childSchemas;
                unset($property["required"]);
            } else {
                if ($propertyRequired === true) {
                    $property["required"]           = true;
                }
            }
            foreach (self::multipleTypes as $multipleType) {
                unset($property[$multipleType]);
            }
            return $property;
        } else {
            throw new RuntimeException("node type for $fullPropertyName does not exist");
        }
    }

    /**
     * @param string $ref
     * @return array
     */
    private function getContentByRef(string $ref) : array {
        $content                                = $this->content ?? [];
        $refs                                   = explode("/", $ref);
        array_shift($refs);
        $nodes                                  = [];
        foreach ($refs as $refKey) {
            $nodes[]                            = $refKey;
            $this->logger->debug("search ref $refKey: found ".join("/", $nodes));
            if (array_key_exists($refKey, $content)) {
                $content                        = $content[$refKey];
            } else {
                throw new RuntimeException("ref ".join("/", $nodes). " does not exist");
            }
        }
        if (count($nodes) === 0) {
            throw new RuntimeException("ref $ref does not exist");
        }
        if (!is_array($content)) {
            throw new RuntimeException("ref ".join("/", $nodes). " exists, but content has to be an array");
        }
        return $content;
    }
}