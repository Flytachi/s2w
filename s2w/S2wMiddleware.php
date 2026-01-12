<?php

namespace S2w;

use Flytachi\Winter\Kernel\Stereotype\Middleware\CorsMiddleware;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class S2wMiddleware extends CorsMiddleware
{
    protected array $origin = [];
    protected array $allowHeaders = ["Authorization", "Accept", "Origin"];
    protected array $exposeHeaders = [];
    protected bool $credentials = false;
    protected int $maxAge = 0;
    protected array $vary = [];
}
