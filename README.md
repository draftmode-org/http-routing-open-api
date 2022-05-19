# Terrazza/Http-Routing-Open-Api
This component support based on an Open Api Yaml file
- getRouting
- validateRouting

The component depends on 
- Terrazza/Validator
- Terrazza/Http-Routing 
 
and 
- converts properties/parameters from the yaml file to ObjectValueSchema(s)
- validate created ObjectValueSchemas against values

## _Object/Classes_
1. [OpenApiReader](#object-reader)
2. [OpenApiRouter](#object-router)
3. [OpenApiRouteValidator](#object-route-validator)
4. [OpenApiYamlValidator](#object-yaml-validator)

<a id="object-reader" name="object-reader"></a>
<a id="user-content-object-reader" name="user-content-object-reader"></a>
### OpenApiReader
_implements: HttpRoutingReaderInterface_
#### method: load
load the given yaml file into the reader property content.
#### method: getRoutes
returns a list of all given routes.<br>
the response is structured like:
- uri
  - method

and has the operationId as content;
```
[
    "/payments" => [
        "get" => "get_operation_id"
    ]
]
```

#### method: getParameterParams
based on a given uri, method and "in"-type this method return all related properties. In addition, this method merges the uri parameters and the method parameters.

>api.yaml
```
paths:
  "/payments":
      parameters:
        - in: query
          name: "paymentFrom"
          type: "string"
          required: true
      get:
        parameters:
          - in: query
            name: "paymentTo"
            type: "string"                 
```
>usage
```
$parameter = $reader->getParameterParams("/payments", "get", "query");
[
    "paymentFrom" => [
        "type" => "string",
        "required" => true,
    ],
    "paymentTo" => [
        "type" => "string",
    ],    
]
```
#### method: getRequestBodyContents
#### method: getRequestBodyParams

<a id="object-router" name="object-router"></a>
<a id="user-content-object-router" name="user-content-object-router"></a>
### OpenApiRouter

<a id="object-route-validator" name="object-route-validator"></a>
<a id="user-content-object-route-validator" name="user-content-object-route-validator"></a>
### OpenApiRouteValidator

<a id="object-yaml-validator" name="object-yaml-validator"></a>
<a id="user-content-object-yaml-validator" name="user-content-object-yaml-validator"></a>
### OpenApiYamlValidator