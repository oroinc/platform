<?php

namespace Oro\Bundle\EntityMergeBundle\DataGrid\Extension\MassAction;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\AbstractMassAction;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;

class MergeMassAction extends AbstractMassAction
{
    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param ConfigProvider      $entityConfigProvider
     * @param TranslatorInterface $translator
     */
    public function __construct(
        ConfigProvider $entityConfigProvider,
        TranslatorInterface $translator
    ) {
        parent::__construct();

        $this->entityConfigProvider = $entityConfigProvider;
        $this->translator = $translator;
    }

    /** @var array */
    protected $requiredOptions = ['route', 'entity_name', 'data_identifier', 'max_element_count'];

    /** @var array */
    protected $defaultOptions = [
        'frontend_handle'  => 'redirect',
        'handler'          => 'oro_entity_merge.mass_action.data_handler',
        'icon'             => 'random',
        'frontend_type'    => 'merge-mass',
        'route'            => 'oro_entity_merge_massaction',
        'data_identifier'  => 'id',
        'route_parameters' => [],
    ];

    /**
     * {@inheritdoc}
     */
    public function setOptions(ActionConfiguration $options)
    {
        $this->setDefaultOptions($options);

        if (isset($options['entity_name'])) {
            $entityConfig = $this->entityConfigProvider->getConfig($options['entity_name']);
            $options['max_element_count'] = $entityConfig->get(
                'max_element_count',
                false,
                EntityMetadata::MAX_ENTITIES_COUNT
            );

            $options['label'] = $this->translator->trans(
                'oro.entity_merge.action.merge',
                ['{{ label }}' => $this->translator->trans($entityConfig->get('label'))]
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
