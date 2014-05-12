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

    /** @var array */
    protected $defaultOptions = array(
        'frontend_handle' => 'redirect',
        'handler' => 'oro_entity_merge.mass_action.data_handler',
        'icon' => 'random',
        'frontend_type' => 'merge-mass',
        'route' => 'oro_entity_merge_massaction',
        'data_identifier' => 'id',
        'route_parameters' => array(),
    );

    /**
     * {@inheritdoc}
     */
    public function setOptions(ActionConfiguration $options)
    {
        $this->setDefaultOptions($options);

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

        return parent::setOptions($options);
    }

    /**
     * @param ActionConfiguration $options
     */
    protected function setDefaultOptions(ActionConfiguration $options)
    {
        foreach ($this->defaultOptions as $name => $value) {
            if (!isset($options[$name])) {
                $options[$name] = $value;
            }
        }
    }
}
