<?php

namespace Oro\Bundle\EntityMergeBundle\DataGrid\Extension\MassAction\Actions\Merge;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\AbstractMassAction;

class MergeMassAction extends AbstractMassAction
{
    /** @var array */
    protected $requiredOptions = ['route', 'entity_name', 'id_property_name', 'max_element_count'];

    /**
     * {@inheritDoc}
     */
    public function setOptions(ActionConfiguration $options)
    {
        if (empty($options['frontend_handle'])) {
            $options['frontend_handle'] = 'redirect';
        }

        if (empty($options['frontend_type'])) {
            $options['frontend_type'] = 'merge-mass';
        }


        if (empty($options['route'])) {
            $options['route'] = 'oro_entity_merge';
        }
        if (empty($options['id_property_name'])) {
            $options['id_property_name'] = 'id';
        }
        if (empty($options['max_element_count'])) {
            $options['max_element_count'] = '5';
        }

        if (empty($options['entity_name'])) {
            throw new \LogicException('There is no option "entity_name" for action "merge".');
        }

        if (empty($options['route_parameters'])) {
            $options['route_parameters'] = array('id_property_name' => $options['id_property_name'],
                'entity_name' => $options['entity_name']);
        }

        return parent::setOptions($options);
    }
}
