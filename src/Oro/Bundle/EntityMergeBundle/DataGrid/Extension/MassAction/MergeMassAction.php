<?php

namespace Oro\Bundle\EntityMergeBundle\DataGrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\AbstractMassAction;

class MergeMassAction extends AbstractMassAction
{
    const MAX_ELEMENT_COUNT = 5;

    /** @var array */
    protected $requiredOptions = ['route', 'entity_name', 'data_identifier', 'max_element_count'];

    /**
     * {@inheritdoc}
     */
    public function setOptions(ActionConfiguration $options)
    {
        if (empty($options['frontend_handle'])) {
            $options['frontend_handle'] = 'redirect';
        }

        if (empty($options['handler'])) {
            $options['handler'] = 'oro_entity_merge.mass_action.data_handler';
        }

        if (empty($options['frontend_type'])) {
            $options['frontend_type'] = 'merge-mass';
        }

        if (empty($options['route'])) {
            $options['route'] = 'oro_entity_merge_massaction';
        }
        if (empty($options['data_identifier'])) {
            $options['data_identifier'] = 'id';
        }
        if (empty($options['max_element_count'])) {
            $options['max_element_count'] = self::MAX_ELEMENT_COUNT;
        }

        if (!isset($options['route_parameters'])) {
            $options['route_parameters'] = array();
        }

        return parent::setOptions($options);
    }
}
