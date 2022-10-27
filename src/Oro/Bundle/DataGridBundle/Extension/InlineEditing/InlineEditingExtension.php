<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Provider\DatagridModeProvider;

/**
 * Enables inline editing on supported columns for grids which has inline editing enabled.
 */
class InlineEditingExtension extends AbstractExtension
{
    private InlineEditingConfigurator $configurator;

    /** {@inheritdoc} */
    protected $excludedModes = [
        DatagridModeProvider::DATAGRID_IMPORTEXPORT_MODE
    ];

    public function __construct(
        InlineEditingConfigurator $configurator
    ) {
        $this->configurator = $configurator;
    }

    public function isApplicable(DatagridConfiguration $config)
    {
        return parent::isApplicable($config)
            && $config->offsetGetByPath(Configuration::ENABLED_CONFIG_PATH);
    }

    public function processConfigs(DatagridConfiguration $config)
    {
        $this->configurator->configureInlineEditingForGrid($config);
        $this->configurator->configureInlineEditingForSupportingColumns($config);
    }

    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data)
    {
        $data->offsetSet(
            Configuration::BASE_CONFIG_KEY,
            $config->offsetGetOr(Configuration::BASE_CONFIG_KEY, [])
        );
    }
}
