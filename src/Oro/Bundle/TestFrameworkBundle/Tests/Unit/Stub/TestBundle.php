<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Stub;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class TestBundle implements BundleInterface
{
    /** @var string */
    protected $name;

    /** @var string */
    protected $path = 'foo/bar';

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    #[\Override]
    public function getName(): string
    {
        return $this->name;
    }

    #[\Override]
    public function getNamespace(): string
    {
        return $this->name;
    }

    #[\Override]
    public function boot()
    {
    }

    #[\Override]
    public function shutdown()
    {
    }

    #[\Override]
    public function build(ContainerBuilder $container)
    {
    }

    #[\Override]
    public function getContainerExtension(): ?ExtensionInterface
    {
    }

    public function getParent()
    {
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    #[\Override]
    public function getPath(): string
    {
        return $this->path;
    }

    #[\Override]
    public function setContainer(?ContainerInterface $container = null)
    {
    }
}
