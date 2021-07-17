<?php

namespace Oro\Bundle\DataGridBundle\Datagrid;

use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderAwareInterface;
use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderAwareTrait;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Exception\RuntimeException;
use Oro\Bundle\DataGridBundle\Extension\Acceptor;
use Oro\Bundle\DataGridBundle\Extension\ExtensionVisitorInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a functionality to build datagrids.
 */
class Builder implements MemoryCacheProviderAwareInterface
{
    use MemoryCacheProviderAwareTrait;

    /** @var string */
    private $baseDatagridClass;

    /** @var string */
    private $acceptorClass;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var ContainerInterface */
    private $dataSources;

    /** @var iterable|ExtensionVisitorInterface[] */
    private $extensions;

    /**
     * @param string                               $baseDatagridClass
     * @param string                               $acceptorClass
     * @param EventDispatcherInterface             $eventDispatcher
     * @param ContainerInterface                   $dataSources
     * @param iterable|ExtensionVisitorInterface[] $extensions
     */
    public function __construct(
        string $baseDatagridClass,
        string $acceptorClass,
        EventDispatcherInterface $eventDispatcher,
        ContainerInterface $dataSources,
        iterable $extensions
    ) {
        $this->baseDatagridClass = $baseDatagridClass;
        $this->acceptorClass = $acceptorClass;
        $this->eventDispatcher = $eventDispatcher;
        $this->dataSources = $dataSources;
        $this->extensions = $extensions;
    }

    /**
     * Create, configure and build datagrid
     *
     * @param DatagridConfiguration $config
     * @param ParameterBag          $parameters
     * @param array                 $additionalParameters
     *
     * @return DatagridInterface
     */
    public function build(DatagridConfiguration $config, ParameterBag $parameters, array $additionalParameters = [])
    {
        /**
         * should be refactored in BAP-6849
         */
        $minified = $parameters->get(ParameterBag::MINIFIED_PARAMETERS);
        if (is_array($minified) && array_key_exists('g', $minified) && is_array($minified['g'])) {
            $parameters->add(array_merge($minified['g'], $additionalParameters));
        }

        /**
         * should be refactored in BAP-6826
         */
        $event = new PreBuild($config, $parameters);
        $this->eventDispatcher->dispatch($event, PreBuild::NAME);

        $class = $config->offsetGetByPath(DatagridConfiguration::BASE_DATAGRID_CLASS_PATH, $this->baseDatagridClass);
        $name  = $config->getName();

        /** @var DatagridInterface $datagrid */
        $datagrid = new $class($name, $config, $parameters);
        if ($datagrid instanceof MemoryCacheProviderAwareInterface) {
            $datagrid->setMemoryCacheProvider($this->getMemoryCacheProvider());
        }
        $datagrid->setScope($config->offsetGetOr('scope'));

        $event = new BuildBefore($datagrid, $config);
        $this->eventDispatcher->dispatch($event, BuildBefore::NAME);

        $acceptor = $this->createAcceptor($config, $parameters);
        $datagrid->setAcceptor($acceptor);

        $acceptor->processConfiguration();
        $this->buildDataSource($datagrid, $config);

        $event = new BuildAfter($datagrid);
        $this->eventDispatcher->dispatch($event, BuildAfter::NAME);

        return $datagrid;
    }

    /**
     * @param DatagridConfiguration $config
     * @param ParameterBag          $parameters
     *
     * @return Acceptor
     */
    private function createAcceptor(DatagridConfiguration $config, ParameterBag $parameters)
    {
        /** @var Acceptor $acceptor */
        $acceptor = new $this->acceptorClass();
        $acceptor->setConfig($config);

        foreach ($this->extensions as $extension) {
            /**
             * ATTENTION: extension object should be cloned cause it can contain some state
             */
            $extension = clone $extension;
            $extension->setParameters($parameters);

            if ($extension->isApplicable($config)) {
                $acceptor->addExtension($extension);
            }
        }
        $acceptor->sortExtensionsByPriority();

        return $acceptor;
    }

    /**
     * Try to find datasource adapter and process it
     * Datasource object should be self-acceptable to grid
     *
     * @throws RuntimeException
     */
    private function buildDataSource(DatagridInterface $grid, DatagridConfiguration $config)
    {
        $sourceType = $config->offsetGetByPath(DatagridConfiguration::DATASOURCE_TYPE_PATH, false);
        if (!$sourceType) {
            throw new RuntimeException('Datagrid source does not configured');
        }

        if (!$this->dataSources->has($sourceType)) {
            throw new RuntimeException(sprintf('Datagrid source "%s" does not exist', $sourceType));
        }

        /** @var DatasourceInterface $dataSource */
        $dataSource = $this->dataSources->get($sourceType);
        $dataSource->process(
            $grid,
            $config->offsetGetByPath(DatagridConfiguration::DATASOURCE_PATH, [])
        );
    }
}
