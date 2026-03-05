<?php

namespace Laragear\ApiManager\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Response
{
    /**
     * Create a new Response instance.
     */
    public function __construct(public readonly string $class)
    {
        //
    }
}
