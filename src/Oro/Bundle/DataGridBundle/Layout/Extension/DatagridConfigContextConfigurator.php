<?php

namespace Oro\Bundle\DataGridBundle\Layout\Extension;

use Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;

/**
 * Sets grid config for given grid name(-s) into layout context
 */
class DatagridConfigContextConfigurator implements ContextConfiguratorInterface
{
    /** @var ManagerInterface */
    private $dataGridManager;

    public function __construct(ManagerInterface $dataGridManager)
    {
        $this->dataGridManager = $dataGridManager;
    }

    /**
     * Sets grid config for given grid name(-s) into layout context
     *
     * {@inheritdoc}
     */
    public function configureContext(ContextInterface $context)
    {
        if (!$context->has('grid_config')) {
            return;
        }

        $context->getResolver()
            ->setDefined(['grid_config'])
            ->setAllowedTypes('grid_config', ['array']);

        $data = $context->getOr('grid_config');

        if (!is_array($data)) {
            return;
        }

        $gridConfig = [];
        foreach ($data as $gridName) {
            if (!is_string($gridName)) {
                throw new \InvalidArgumentException(
                    sprintf('The "grid_config" value must be a string, but "%s" given.', gettype($gridName))
                );
            }

            $config = $this->dataGridManager->getConfigurationForGrid($gridName);
            $gridConfig[$gridName] = $config->toArray();
        }

        $context->set('grid_config', $gridConfig);
    }
}
