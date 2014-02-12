<?php

namespace Oro\Bundle\EntityMergeBundle\DataGrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\AbstractMassAction;
use Oro\Bundle\EntityMergeBundle\Metadata\MetadataRegistry;

class MergeMassAction extends AbstractMassAction
{
    /**
     * @var MetadataRegistry
     */
    protected $metadataRegistry;

    /**
     * @param MetadataRegistry $metadataRegistry
     */
    public function __construct(MetadataRegistry $metadataRegistry)
    {
        $this->metadataRegistry = $metadataRegistry;
    }

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

        $options['max_element_count'] = $this
            ->metadataRegistry
            ->getEntityMetadata($options['entity_name'])
            ->getMaxEntitiesCount();

        if (!isset($options['route_parameters'])) {
            $options['route_parameters'] = array();
        }

        return parent::setOptions($options);
    }
}
