<?php

namespace Oro\Component\Layout\Extension\Theme;

use Oro\Component\Layout\ContextAwareInterface;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\Extension\AbstractExtension;
use Oro\Component\Layout\Extension\Theme\Model\DependencyInitializer;
use Oro\Component\Layout\Extension\Theme\PathProvider\PathProviderInterface;
use Oro\Component\Layout\Extension\Theme\ResourceProvider\ResourceProviderInterface;
use Oro\Component\Layout\Extension\Theme\Visitor\VisitorInterface;
use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\LayoutUpdateInterface;
use Oro\Component\Layout\Loader\Generator\ElementDependentLayoutUpdateInterface;
use Oro\Component\Layout\Loader\LayoutUpdateLoaderInterface;

/**
 * Provides layout updates for the theme found in context.
 */
class ThemeExtension extends AbstractExtension
{
    const THEME_KEY = 'theme';

    /** @var LayoutUpdateLoaderInterface */
    protected $loader;

    /** @var DependencyInitializer */
    protected $dependencyInitializer;

    /** @var PathProviderInterface */
    protected $pathProvider;

    /** @var ResourceProviderInterface */
    protected $resourceProvider;

    /** @var VisitorInterface[] */
    protected $visitors = [];

    /** @var array */
    protected $updates = [];

    public function __construct(
        LayoutUpdateLoaderInterface $loader,
        DependencyInitializer $dependencyInitializer,
        PathProviderInterface $pathProvider,
        ResourceProviderInterface $resourceProvider
    ) {
        $this->loader = $loader;
        $this->dependencyInitializer = $dependencyInitializer;
        $this->pathProvider = $pathProvider;
        $this->resourceProvider = $resourceProvider;
    }

    public function addVisitor(VisitorInterface $visitor)
    {
        $this->visitors[] = $visitor;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadLayoutUpdates(LayoutItemInterface $item)
    {
        $idOrAlias = $item->getAlias() ? : $item->getId();
        $context = $item->getContext();
        $contextHash = $context->getHash();
        $this->updates[$contextHash] = [];
        if ($context->getOr(self::THEME_KEY)) {
            $rootId = $item->getRootId() ?: $idOrAlias;
            $paths = $this->getPaths($context);
            $files = $this->resourceProvider->findApplicableResources($paths);
            foreach ($files as $file) {
                $layoutUpdate = $this->loadLayoutUpdate($file);
                if (!$layoutUpdate) {
                    continue;
                }

                $layoutItemId = $layoutUpdate instanceof ElementDependentLayoutUpdateInterface
                    ? $layoutUpdate->getElement()
                    : $rootId;

                $this->updates[$contextHash][$layoutItemId][] = $layoutUpdate;
            }
        }

        foreach ($this->visitors as $visitor) {
            $visitor->walkUpdates($this->updates[$contextHash], $context);
        }

        return $this->updates[$contextHash];
    }

    protected function loadLayoutUpdate(string $file): ?LayoutUpdateInterface
    {
        $update = $this->loader->load($file);
        if ($update) {
            $this->dependencyInitializer->initialize($update);
        }

        return $update;
    }

    /**
     * Return paths that comes from provider and returns array of resource files
     *
     * @param ContextInterface $context
     *
     * @return array
     */
    protected function getPaths(ContextInterface $context)
    {
        if ($this->pathProvider instanceof ContextAwareInterface) {
            $this->pathProvider->setContext($context);
        }

        return $this->pathProvider->getPaths([]);
    }
}
