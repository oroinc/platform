<?php

namespace Oro\Bundle\EntityMergeBundle\DataGrid\Extension\MassAction;

use Symfony\Component\Translation\TranslatorInterface;

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
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param MetadataRegistry    $metadataRegistry
     * @param TranslatorInterface $translator
     */
    public function __construct(MetadataRegistry $metadataRegistry, TranslatorInterface $translator)
    {
        $this->metadataRegistry = $metadataRegistry;
        $this->translator       = $translator;
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

        if (empty($options['icon'])) {
            $options['icon'] = 'random';
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

        if (isset($options['entity_name'])) {
            $metadata = $this
                ->metadataRegistry
                ->getEntityMetadata($options['entity_name']);

            $options['max_element_count'] = $metadata->getMaxEntitiesCount();

            $pluralLabel = $this->translator
                ->trans($metadata->get('label'));

            $options['label'] = $this->translator
                ->trans(
                    'oro.entity_merge.action.merge',
                    ['{{ label }}' => strtolower($pluralLabel)]
                );
        }

        if (!isset($options['route_parameters'])) {
            $options['route_parameters'] = array();
        }

        return parent::setOptions($options);
    }
}
