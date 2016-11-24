<?php

namespace Oro\Bundle\DataGridBundle\Datagrid;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Exception\RuntimeException;
use Oro\Bundle\DataGridBundle\Extension\Acceptor;
use Oro\Bundle\DataGridBundle\Extension\ExtensionVisitorInterface;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

class Builder
{
    /**
     * @deprecated Since 1.9, will be removed after 1.11.
     * @see DatagridConfiguration::DATASOURCE_PATH
     */
    const DATASOURCE_PATH           = '[source]';

    /**
     * @deprecated Since 1.9, will be removed after 1.11.
     * @see DatagridConfiguration::DATASOURCE_TYPE_PATH, DatagridConfiguration::getDatasourceType
     */
    const DATASOURCE_TYPE_PATH      = '[source][type]';

    /**
     * @deprecated Since 1.9, will be removed after 1.11.
     * @see DatagridConfiguration::ACL_RESOURCE_PATH, DatagridConfiguration::getAclResource
     */
    const DATASOURCE_ACL_PATH       = '[source][acl_resource]';

    /**
     * @deprecated Since 1.9, will be removed after 1.11.
     * @see DatagridConfiguration::BASE_DATAGRID_CLASS_PATH
     */
    const BASE_DATAGRID_CLASS_PATH  = '[options][base_datagrid_class]';

    /**
     * @deprecated Since 1.9, will be removed after 1.11.
     * @see DatagridConfiguration::DATASOURCE_SKIP_ACL_APPLY_PATH, DatagridConfiguration::isDatasourceSkipAclApply
     */
    const DATASOURCE_SKIP_ACL_CHECK = '[options][skip_acl_check]';

    /**
     * @deprecated Since 1.9, will be removed after 1.11.
     * @see DatagridConfiguration::DATASOURCE_SKIP_COUNT_WALKER_PATH
     */
    const DATASOURCE_SKIP_COUNT_WALKER_PATH = '[options][skip_count_walker]';

    /** @var string */
    protected $baseDatagridClass;

    /** @var string */
    protected $acceptorClass;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var DatasourceInterface[] */
    protected $dataSources = [];

    /** @var ExtensionVisitorInterface[] */
    protected $extensions = [];

    /**
     * @param                          $baseDatagridClass
     * @param                          $acceptorClass
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct($baseDatagridClass, $acceptorClass, EventDispatcherInterface $eventDispatcher)
    {
        $this->baseDatagridClass = $baseDatagridClass;
        $this->acceptorClass     = $acceptorClass;
        $this->eventDispatcher   = $eventDispatcher;
    }

    /**
     * Create, configure and build datagrid
     *
     * @param DatagridConfiguration $config
     * @param ParameterBag          $parameters
     *
     * @return DatagridInterface
     */
    public function build(DatagridConfiguration $config, ParameterBag $parameters)
    {
        /**
         * @TODO: should be refactored in BAP-6849
         */
        $minified = $parameters->get(ParameterBag::MINIFIED_PARAMETERS);
        if (is_array($minified) && array_key_exists('g', $minified) && is_array($minified['g'])) {
            $gridParams = [];
            foreach ($minified['g'] as $gridParamName => $gridParamValue) {
                $gridParams[$gridParamName] = $gridParamValue;
            }
            $parameters->add($gridParams);
        }

        /**
         * @TODO: should be refactored in BAP-6826
         */
        $event = new PreBuild($config, $parameters);
        $this->eventDispatcher->dispatch(PreBuild::NAME, $event);

        $class = $config->offsetGetByPath(DatagridConfiguration::BASE_DATAGRID_CLASS_PATH, $this->baseDatagridClass);
        $name  = $config->getName();

        /** @var DatagridInterface $datagrid */
        $datagrid = new $class($name, $config, $parameters);
        $datagrid->setScope($config->offsetGetOr('scope'));

        $event = new BuildBefore($datagrid, $config);
        $this->eventDispatcher->dispatch(BuildBefore::NAME, $event);

        $acceptor = $this->createAcceptor($config, $parameters);
        $datagrid->setAcceptor($acceptor);

        $acceptor->processConfiguration();
        $this->buildDataSource($datagrid, $config);

        $event = new BuildAfter($datagrid);
        $this->eventDispatcher->dispatch(BuildAfter::NAME, $event);

        return $datagrid;
    }

    /**
     * Register datasource type
     * Automatically registered services tagged by oro_datagrid.datasource tag
     *
     * @param string              $type
     * @param DatasourceInterface $dataSource
     *
     * @return $this
     */
    public function registerDatasource($type, DatasourceInterface $dataSource)
    {
        $this->dataSources[$type] = $dataSource;

        return $this;
    }

    /**
     * Register extension
     * Automatically registered services tagged by oro_datagrid.extension tag
     *
     * @param ExtensionVisitorInterface $extension
     *
     * @return $this
     */
    public function registerExtension(ExtensionVisitorInterface $extension)
    {
        $this->extensions[] = $extension;

        return $this;
    }

    /**
     * @param DatagridConfiguration $config
     * @param ParameterBag          $parameters
     *
     * @return Acceptor
     */
    protected function createAcceptor(DatagridConfiguration $config, ParameterBag $parameters)
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
     * @param DatagridInterface     $grid
     * @param DatagridConfiguration $config
     *
     * @throws RuntimeException
     */
    protected function buildDataSource(DatagridInterface $grid, DatagridConfiguration $config)
    {
        $sourceType = $config->offsetGetByPath(DatagridConfiguration::DATASOURCE_TYPE_PATH, false);
        if (!$sourceType) {
            throw new RuntimeException('Datagrid source does not configured');
        }

        if (!isset($this->dataSources[$sourceType])) {
            throw new RuntimeException(sprintf('Datagrid source "%s" does not exist', $sourceType));
        }

        $this->dataSources[$sourceType]->process(
            $grid,
            $config->offsetGetByPath(DatagridConfiguration::DATASOURCE_PATH, [])
        );
    }
}
