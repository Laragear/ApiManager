<?php

namespace Laragear\ApiManager\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Action
{
    /**
     * Create a new Action instance.
     */
    public function __construct(
        public string $name,
        public string $method,
        public ?string $path = null,
        public ?string $response = null,
    ) {
        if (null === $this->path) {
            [$this->path, $this->method] = [$this->method, 'get'];
        }

        $this->method = strtolower($this->method);
    }
}
