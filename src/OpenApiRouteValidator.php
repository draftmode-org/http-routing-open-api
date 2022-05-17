<?php
namespace Terrazza\Component\HttpRouting\OpenApi;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Terrazza\Component\Http\Request\HttpServerRequestInterface;
use Terrazza\Component\HttpRouting\HttpRoute;
use Terrazza\Component\HttpRouting\HttpRoutingValidatorInterface;

class OpenApiRouteValidator implements HttpRoutingValidatorInterface {
    private string $routingFileName;
    private LoggerInterface $logger;
    private string $defaultContentType;

    public function __construct(string $routingFileName, LoggerInterface $logger, ?string $defaultContentType=null) {
        $this->routingFileName                      = $routingFileName;
        $this->logger                               = $logger;
        $this->defaultContentType                   = $defaultContentType ?? "application/json";
    }

    /**
     * @param HttpRoute $route
     * @param HttpServerRequestInterface $request
     */
    public function validate(HttpRoute $route, HttpServerRequestInterface $request) : void {
        //
        $reader                                     = new OpenApiReader($this->logger);
        $validator                                  = new OpenApiYamlValidator($this->logger);
        //
        $yaml                                       = $reader->load($this->routingFileName);
        $uri                                        = $route->getRoutePath();
        $method                                     = $route->getRouteMethod();
        //
        // validate pathParam
        //
        if ($params = $yaml->getParameterParams($uri, $method, "path")) {
            $validator->validate("pathParam", $request->getPathParams($uri), $params);
        }
        //
        // validate queryParam
        //
        if ($params = $yaml->getParameterParams($uri, $method, "query")) {
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
        if ($requestBodyParams = $yaml->getRequestBodyContents($uri, $method)) {
            $requestContentType                     = $request->getHeaderLine("Content-Type");
            if (strlen($requestContentType) === 0) {
                $requestContentType                 = $this->defaultContentType;
            }
            $requestBodyParams                      = $yaml->getRequestBodyParams($requestBodyParams, $requestContentType);
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