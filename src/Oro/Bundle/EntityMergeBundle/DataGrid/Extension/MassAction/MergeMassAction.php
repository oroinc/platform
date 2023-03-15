<?php

namespace Oro\Bundle\EntityMergeBundle\DataGrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\AbstractMassAction;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;

/**
 * The "merge" mass action.
 *
 * Usage:
 * merge:
 *     type: merge
 *     entity_name: Acme\Bundle\FooBundle\Entity\Bar
 *     data_identifier: b.id
 */
class MergeMassAction extends AbstractMassAction
{
    private const DEFAULT_OPTIONS = [
        'frontend_handle'  => 'redirect',
        'handler'          => 'oro_entity_merge.mass_action.data_handler',
        'icon'             => 'random',
        'frontend_type'    => 'merge-mass',
        'label'            => 'oro.entity_merge.action.merge',
        'route'            => 'oro_entity_merge_massaction',
        'route_parameters' => [],
        'data_identifier'  => 'id'
    ];

    /** @var array */
    protected $requiredOptions = ['route', 'entity_name', 'data_identifier', 'max_element_count'];

    private ConfigProvider $entityConfigProvider;

    public function __construct(ConfigProvider $entityConfigProvider)
    {
        parent::__construct();
        $this->entityConfigProvider = $entityConfigProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function setOptions(ActionConfiguration $options)
    {
        foreach (self::DEFAULT_OPTIONS as $name => $value) {
            if (!isset($options[$name])) {
                $options[$name] = $value;
            }
        }

        if (isset($options['entity_name'])) {
            $options['max_element_count'] = $this->entityConfigProvider->getConfig($options['entity_name'])->get(
                'max_element_count',
                false,
                EntityMetadata::MAX_ENTITIES_COUNT
            );
        }

        return parent::setOptions($options);
    }
}
