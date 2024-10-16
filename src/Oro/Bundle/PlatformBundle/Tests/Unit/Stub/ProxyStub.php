<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Stub;

use Closure;
use Doctrine\Common\Proxy\Proxy;

class ProxyStub implements Proxy
{
    public string $class;

    public int $id;

    public function __construct(string $class, int $id)
    {
        $this->class = $class;
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    #[\Override]
    public function __setInitialized($initialized)
    {
    }

    #[\Override]
    public function __setInitializer(?Closure $initializer = null)
    {
    }

    #[\Override]
    public function __getInitializer()
    {
    }

    #[\Override]
    public function __setCloner(?Closure $cloner = null)
    {
    }

    #[\Override]
    public function __getCloner()
    {
    }

    #[\Override]
    public function __getLazyProperties()
    {
    }

    #[\Override]
    public function __load()
    {
    }

    #[\Override]
    public function __isInitialized()
    {
    }
}
