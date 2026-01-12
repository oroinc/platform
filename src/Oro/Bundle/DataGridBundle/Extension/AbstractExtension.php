<?php

namespace Oro\Bundle\DataGridBundle\Extension;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;

/**
 * Provides common functionality for datagrid extensions.
 *
 * This base class implements the extension visitor interface with empty default implementations
 * for all visitor methods, allowing subclasses to override only the methods they need.
 * It also provides configuration validation, parameter management, and datagrid mode filtering capabilities.
 * Subclasses should extend this to create specific datagrid extensions (e.g., sorters, filters, actions).
 */
abstract class AbstractExtension implements ExtensionVisitorInterface
{
    /**
     * @var ParameterBag
     */
    protected $parameters;

    /**
     * Full list of datagrid modes
     * @see \Oro\Bundle\DataGridBundle\Provider\DatagridModeProvider
     *
     * @var array of modes that are not supported by this extension
     */
    protected $excludedModes = [];

    #[\Override]
    public function isApplicable(DatagridConfiguration $config)
    {
        return $this->isExtensionSupportedDatagridModes();
    }

    #[\Override]
    public function processConfigs(DatagridConfiguration $config)
    {
    }

    #[\Override]
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
    }

    #[\Override]
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data)
    {
    }

    #[\Override]
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
    }

    #[\Override]
    public function getPriority()
    {
        // default priority if not overridden by child
        return 0;
    }

    /**
     * Validate configuration
     *
     * @param ConfigurationInterface      $configuration
     * @param                             $config
     *
     * @return array
     */
    protected function validateConfiguration(ConfigurationInterface $configuration, $config)
    {
        $processor = new Processor();
        return $processor->processConfiguration(
            $configuration,
            $config
        );
    }

    /**
     * Getter for parameters
     *
     * @return ParameterBag
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Set instance of parameters used for current grid
     */
    #[\Override]
    public function setParameters(ParameterBag $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * Checking that extension is supported all datagrid modes.
     *
     * @return bool
     */
    private function isExtensionSupportedDatagridModes()
    {
        $datagridModes = $this->getParameters()->get(ParameterBag::DATAGRID_MODES_PARAMETER, []);
        if ([] === $datagridModes || [] === $this->excludedModes) {
            return true;
        }

        return empty(array_intersect($this->excludedModes, $datagridModes));
    }
}
