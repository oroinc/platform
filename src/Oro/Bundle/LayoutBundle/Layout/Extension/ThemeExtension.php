<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension;

use Psr\Log\NullLogger;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\Options;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\Extension\AbstractExtension;
use Oro\Component\Layout\ContextConfiguratorInterface;

use Oro\Bundle\LayoutBundle\Layout\Loader\FileResource;
use Oro\Bundle\LayoutBundle\Layout\Loader\ResourceMatcher;
use Oro\Bundle\LayoutBundle\Layout\Loader\LoaderInterface;
use Oro\Bundle\LayoutBundle\Layout\Loader\ResourceIterator;
use Oro\Bundle\LayoutBundle\Layout\Loader\ResourceFactoryInterface;

class ThemeExtension extends AbstractExtension implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var array */
    protected $resources;

    /** @var ResourceFactoryInterface */
    protected $factory;

    /** @var LoaderInterface */
    protected $loader;

    /** @var DependencyInitializer */
    protected $dependencyInitializer;

    /** @var ResourceMatcher */
    protected $matcher;

    /**
     * @param array                    $resources
     * @param ResourceFactoryInterface $factory
     * @param LoaderInterface          $loader
     * @param DependencyInitializer    $dependencyInitializer
     * @param ResourceMatcher          $matcher
     */
    public function __construct(
        array $resources,
        ResourceFactoryInterface $factory,
        LoaderInterface $loader,
        DependencyInitializer $dependencyInitializer,
        ResourceMatcher $matcher
    ) {
        $this->resources             = $resources;
        $this->loader                = $loader;
        $this->factory               = $factory;
        $this->dependencyInitializer = $dependencyInitializer;
        $this->matcher               = $matcher;
        $this->setLogger(new NullLogger());
    }

    /**
     * {@inheritdoc}
     */
    protected function loadLayoutUpdates(ContextInterface $context)
    {
        $updates = [];

        if ($context->getOr('theme')) {
            $this->matcher->setContext($context);

            $iterator = new ResourceIterator($this->factory, $this->resources);
            $iterator->setMatcher($this->matcher);
            foreach ($iterator as $resource) {
                if ($this->loader->supports($resource)) {
                    $update = $this->loader->load($resource);
                    $this->dependencyInitializer->initialize($update);
                    $updates[] = $update;
                } else {
                    $this->logUnknownResource($resource);
                }
            }
        }

        return ['root' => $updates];
    }

    /**
     * @param FileResource $resource
     */
    protected function logUnknownResource(FileResource $resource)
    {
        $this->logger->notice(sprintf('Skipping resource "%s" because loader for it not found', $resource));
    }
}
