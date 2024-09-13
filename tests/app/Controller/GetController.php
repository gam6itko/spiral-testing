<?php

declare(strict_types=1);

namespace Spiral\Testing\Tests\App\Controller;

use Psr\Http\Message\ServerRequestInterface;
use Spiral\Core\Internal\Introspector;
use Spiral\Router\Annotation\Route;

class GetController
{
    #[Route('/get/query-params', 'get.queryParams')]
    public function queryParams(ServerRequestInterface $request): array
    {
        return $request->getQueryParams();
    }

    #[Route('/get/headers', 'get.headers')]
    public function headers(ServerRequestInterface $request): array
    {
        return $request->getHeaders();
    }

    #[Route('/get/scopes', 'get.scopes')]
    public function scopes(ServerRequestInterface $request): array
    {
        return Introspector::scopeNames();
    }
}
