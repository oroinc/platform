<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension;

use Psr\Log\NullLogger;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;

use Oro\Component\ConfigExpression\ExpressionAssembler;
use Oro\Component\ConfigExpression\ExpressionAssemblerAwareInterface;
use Oro\Component\Layout\LayoutUpdateInterface;
use Oro\Component\Layout\Extension\AbstractExtension;

use Oro\Bundle\LayoutBundle\Theme\ThemeManager;
use Oro\Bundle\LayoutBundle\Layout\Loader\FileResource;
use Oro\Bundle\LayoutBundle\Layout\Loader\LoaderInterface;
use Oro\Bundle\LayoutBundle\Layout\Loader\ThemeResourceIterator;
use Oro\Bundle\LayoutBundle\Layout\Loader\ResourceFactoryInterface;

class ThemeExtension extends AbstractExtension implements LoggerAwareInterface, ExpressionAssemblerAwareInterface
{
    use LoggerAwareTrait;

    /** @var array */
    protected $resources;

    /** @var ThemeManager */
    protected $manager;

    /** @var ResourceFactoryInterface */
    protected $factory;

    /** @var LoaderInterface */
    protected $loader;

    /** @var ExpressionAssembler */
    protected $expressionAssembler;

    /**
     * @param array                    $resources
     * @param ThemeManager             $manager
     * @param ResourceFactoryInterface $factory
     * @param LoaderInterface          $loader
     */
    public function __construct(
        array $resources,
        ThemeManager $manager,
        ResourceFactoryInterface $factory,
        LoaderInterface $loader
    ) {
        $this->resources = $resources;
        $this->manager   = $manager;
        $this->loader    = $loader;
        $this->factory   = $factory;
        $this->setLogger(new NullLogger());
    }

    /**
     * {@inheritdoc}
     */
    protected function loadLayoutUpdates()
    {
        $updates   = $skipped = [];
        $theme     = $this->manager->getTheme();
        $directory = $theme->getDirectory();

        $themeResources = isset($this->resources[$directory]) ? $this->resources[$directory] : [];
        foreach (new ThemeResourceIterator($this->factory, $themeResources) as $resource) {
            if ($this->loader->supports($resource)) {
                $updates[] = $this->loader->load($resource);
            } else {
                $skipped[] = $resource;
            }
        }

        array_walk($skipped, [$this, 'logUnknownResource']);
        array_walk($updates, [$this, 'ensureDependenciesInitialized']);

        return ['root' => $updates];
    }

    /**
     * {@inheritdoc}
     */
    public function setAssembler(ExpressionAssembler $assembler)
    {
        $this->expressionAssembler = $assembler;
    }

    /**
     * Initializes layout update object dependencies
     *
     * @param LayoutUpdateInterface $layoutUpdate
     */
    protected function ensureDependenciesInitialized(LayoutUpdateInterface $layoutUpdate)
    {
        // TODO find generic solution for dependency initialization
        if ($layoutUpdate instanceof ExpressionAssemblerAwareInterface) {
            $layoutUpdate->setAssembler($this->expressionAssembler);
        }
    }

    /**
     * @param FileResource $resource
     */
    protected function logUnknownResource(FileResource $resource)
    {
        $this->logger->notice(sprintf('Skipping resource "%s" because loader for it not found', $resource));
    }
}
