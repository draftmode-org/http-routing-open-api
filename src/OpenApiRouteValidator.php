<?php
namespace Terrazza\Component\HttpRouting\OpenApi;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Terrazza\Component\Http\Request\HttpServerRequestInterface;
use Terrazza\Component\HttpRouting\HttpRoute;
use Terrazza\Component\HttpRouting\HttpRoutingValidatorInterface;

class OpenApiRouteValidator implements HttpRoutingValidatorInterface {
    private OpenApiReaderInterface $reader;
    private LoggerInterface $logger;
    private string $defaultContentType;

    public function __construct(LoggerInterface $logger, OpenApiReaderInterface $openApiReader, ?string $defaultContentType=null) {
        $this->reader                               = $openApiReader;
        $this->logger                               = $logger;
        $this->defaultContentType                   = $defaultContentType ?? "application/json";
    }

    public function load(string $yamlFileName) {
        $reader                                     = clone $this;
        $reader->reader                             = $this->reader->load($yamlFileName);
        return $reader;
    }

    /**
     * @param HttpRoute $route
     * @param HttpServerRequestInterface $request
     */
    public function validate(HttpRoute $route, HttpServerRequestInterface $request) : void {
        //
        $validator                                  = new OpenApiYamlValidator($this->logger);
        //
        $uri                                        = $route->getRoutePath();
        $method                                     = $route->getRouteMethod();
        //
        // validate pathParam
        //
        if ($params = $this->reader->getParameterParams($uri, $method, "path")) {
            $validator->validate("pathParam", $request->getPathParams($uri), $params);
        }
        //
        // validate queryParam
        //
        if ($params = $this->reader->getParameterParams($uri, $method, "query")) {
            $validator->validate("queryParam", $request->getQueryParams(), $params);
        }
        /*
         * actually not implemented, knowledge
         *
        if ($params = $yaml->getParameterParams($uri, $method, "header")) {
            $validator->validate("headerParam", $request->getCookieParams(), $params);
        }
        */
        /*
         * actually not implemented, knowledge
         *
        if ($params = $yaml->getParameterParams($uri, $method, "cookie")) {
            $validator->validate("cookieParam", $request->getCookieParams(), $params);
        }
        */
        //
        // validate requestBody
        //
        if ($requestBodyParams = $this->reader->getRequestBodyContents($uri, $method)) {
            $requestContentType                     = $request->getHeaderLine("Content-Type");
            if (strlen($requestContentType) === 0) {
                $requestContentType                 = $this->defaultContentType;
            }
            $requestBodyParams                      = $this->reader->getRequestBodyParams($requestBodyParams, $requestContentType);
            $requestBody                            = $this->getRequestBodyEncoded($requestContentType, $request->getBody()->getContents());
            $validator->validate("requestBody", $requestBody, $requestBodyParams);
        }
    }

    /**
     * @param string $contentType
     * @param $content
     * @return mixed|null
     */
    private function getRequestBodyEncoded(string $contentType, $content) {
        if (strlen($content)) {
            if (preg_match("#(application/json)|(application/vnd.+\+json)#", $contentType, $matches)) {
                $contentEncoded                     = json_decode($content);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $contentEncoded;
                }
                throw new InvalidArgumentException("body content could not be encoded as json");
            }
        }
        return null;
    }
}