<?php
namespace Terrazza\Component\HttpRouting\OpenApi;
use Psr\Log\LoggerInterface;
use Terrazza\Component\Http\Request\HttpServerRequestInterface;
use Terrazza\Component\HttpRouting\HttpRoute;
use Terrazza\Component\HttpRouting\HttpRoutingInterface;
use Terrazza\Component\Routing\IRouteMatcher;
use Terrazza\Component\Routing\Route;
use Terrazza\Component\Routing\RouteMatcher;
use Terrazza\Component\Routing\RouteSearch;

class OpenApiRouter implements HttpRoutingInterface {
    private LoggerInterface $logger;
    private OpenApiReaderInterface $reader;
    private IRouteMatcher $routeMatcher;

    public function __construct(OpenApiReaderInterface $reader, LoggerInterface $logger) {
        $this->logger                               = $logger;
        $this->reader                               = $reader;
        $this->routeMatcher                         = new RouteMatcher($logger);
    }

    /**
     * @param HttpServerRequestInterface $request
     * @return HttpRoute|null
     */
    public function getRoute(HttpServerRequestInterface $request) :?HttpRoute {
        $this->logger->debug("getRoute");

        $routeSearch                                = new RouteSearch(
            $request->getUri()->getPath(),
            $request->getMethod(),
        );

        $skipMethods                                = ["parameters", "summary", "head"];
        foreach ($this->reader->getRoutes() as $uri => $methods) {
            foreach ($methods as $method => $operationId) {
                if (in_array($method, $skipMethods)) continue;
                $this->logger->debug("...search for $uri / $method");
                $route                              = new Route(
                    $uri,
                    $method
                );
                if ($this->routeMatcher->routeMatch($routeSearch, $route)) {
                    $this->logger->debug("...route found, use routeHandlerClass $operationId");
                    return new HttpRoute(
                        $uri,
                        $method,
                        $operationId
                    );
                }
            }
        }
        return null;
    }
}
