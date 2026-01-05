<?php

namespace Oro\Bundle\DataGridBundle\Extension\Mode;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;

class ModeExtension extends AbstractExtension
{
    public const MODE_OPTION_PATH = '[options][mode]';

    public const MODE_SERVER = 'server';
    public const MODE_CLIENT = 'client';

    #[\Override]
    public function isApplicable(DatagridConfiguration $config)
    {
        return
            parent::isApplicable($config)
            && $this->getMode($config) !== self::MODE_SERVER;
    }

    #[\Override]
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data)
    {
        $data->offsetSetByPath('mode', $this->getMode($config));
    }

    /**
     * @param DatagridConfiguration $config
     * @return string|null
     */
    protected function getMode(DatagridConfiguration $config)
    {
        return $config->offsetGetByPath(self::MODE_OPTION_PATH, self::MODE_SERVER);
    }
}
