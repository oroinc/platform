<?php

namespace Oro\Bundle\ApiBundle\Collection;

use Oro\Component\ChainProcessor\ParameterBag;

/**
 * The container for key/value pairs where keys are case-insensitive.
 */
class CaseInsensitiveParameterBag extends ParameterBag
{
    #[\Override]
    public function has(string $key): bool
    {
        return parent::has(strtolower($key));
    }

    #[\Override]
    public function get(string $key): mixed
    {
        return parent::get(strtolower($key));
    }

    #[\Override]
    public function set(string $key, mixed $value): void
    {
        parent::set(strtolower($key), $value);
    }

    #[\Override]
    public function remove(string $key): void
    {
        parent::remove(strtolower($key));
    }
}
