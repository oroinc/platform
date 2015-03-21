<?php

namespace Oro\Component\Layout\Extension\Theme;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextAwareInterface;
use Oro\Component\Layout\Extension\AbstractExtension;
use Oro\Component\Layout\Extension\Theme\Model\DependencyInitializer;
use Oro\Component\Layout\Extension\Theme\Loader\LoaderInterface;
use Oro\Component\Layout\Extension\Theme\Loader\ResourceIterator;
use Oro\Component\Layout\Extension\Theme\Loader\PathProviderInterface;
use Oro\Component\Layout\Extension\Theme\Loader\ResourceFactoryInterface;
use Oro\Component\Layout\Extension\Theme\Generator\ElementDependentLayoutUpdateInterface;

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

    /** @var PathProviderInterface */
    protected $pathProvider;

    /**
     * @param array                    $resources
     * @param ResourceFactoryInterface $factory
     * @param LoaderInterface          $loader
     * @param DependencyInitializer    $dependencyInitializer
     * @param PathProviderInterface    $provider
     */
    public function __construct(
        array $resources,
        ResourceFactoryInterface $factory,
        LoaderInterface $loader,
        DependencyInitializer $dependencyInitializer,
        PathProviderInterface $provider
    ) {
        $this->resources             = $resources;
        $this->loader                = $loader;
        $this->factory               = $factory;
        $this->dependencyInitializer = $dependencyInitializer;
        $this->pathProvider          = $provider;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadLayoutUpdates(ContextInterface $context)
    {
        $updates = [];

        if ($context->getOr('theme')) {
            $iterator = new ResourceIterator($this->factory, $this->findApplicableResources($context));
            foreach ($iterator as $resource) {
                if ($this->loader->supports($resource)) {
                    $update = $this->loader->load($resource);
                    $this->dependencyInitializer->initialize($update);
                    $el             = $update instanceof ElementDependentLayoutUpdateInterface
                        ? $update->getElement()
                        : 'root';
                    $updates[$el][] = $update;
                } elseif ($this->logger) {
                    $this->logger->notice(
                        sprintf('Skipping resource "%s" because loader for it not found', $resource)
                    );
                }
            }
        }

        return $updates;
    }

    /**
     * Filters resources by paths that comes from provider and returns array of resource files
     *
     * @param ContextInterface $context
     *
     * @return array
     */
    protected function findApplicableResources(ContextInterface $context)
    {
        if ($this->pathProvider instanceof ContextAwareInterface) {
            $this->pathProvider->setContext($context);
        }

        $result = [];
        $paths  = $this->pathProvider->getPaths([]);
        foreach ($paths as $path) {
            $pathArray = explode(PathProviderInterface::DELIMITER, $path);

            $value = $this->resources;
            for ($i = 0, $length = count($pathArray); $i < $length; ++$i) {
                $value = $this->readValue($value, $pathArray[$i]);

                if (null === $value) {
                    break;
                }
            }

            if ($value && is_array($value)) {
                $result = array_merge($result, array_filter($value, 'is_string'));
            }
        }

        return $result;
    }

    /**
     * @param array  $array
     * @param string $property
     *
     * @return mixed
     */
    protected function readValue(&$array, $property)
    {
        if (is_array($array) && isset($array[$property])) {
            return $array[$property];
        }

        return null;
    }
}
