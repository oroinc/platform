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

    public function __setInitialized($initialized)
    {
    }

    public function __setInitializer(?Closure $initializer = null)
    {
    }

    public function __getInitializer()
    {
    }

    public function __setCloner(?Closure $cloner = null)
    {
    }

    public function __getCloner()
    {
    }

    public function __getLazyProperties()
    {
    }

    public function __load()
    {
    }

    public function __isInitialized()
    {
    }
}
