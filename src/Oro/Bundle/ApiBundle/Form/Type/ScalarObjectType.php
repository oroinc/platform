<?php

namespace Oro\Bundle\ApiBundle\Form\Type;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Form\EventListener\ScalarObjectListener;
use Oro\Bundle\ApiBundle\Form\FormHelper;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type for scalar field that is used if a collapsed association should be represented as a field in API.
 */
class ScalarObjectType extends AbstractType
{
    private FormHelper $formHelper;

    public function __construct(FormHelper $formHelper)
    {
        $this->formHelper = $formHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var EntityMetadata $metadata */
        $metadata = $options['metadata'];
        /** @var EntityDefinitionConfig $config */
        $config = $options['config'];

        $fieldName = $options['data_property'];
        $this->formHelper->addFormField(
            $builder,
            ConfigUtil::IGNORE_PROPERTY_PATH,
            $config->getField($fieldName),
            $metadata->getProperty($fieldName),
            ['required' => false, 'error_bubbling' => true, 'property_path' => $fieldName],
            true
        );

        $builder->addEventSubscriber(new ScalarObjectListener());
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('error_bubbling', false)
            ->setRequired(['metadata', 'config', 'data_property'])
            ->setAllowedTypes('metadata', [EntityMetadata::class])
            ->setAllowedTypes('config', [EntityDefinitionConfig::class]);
    }
}
