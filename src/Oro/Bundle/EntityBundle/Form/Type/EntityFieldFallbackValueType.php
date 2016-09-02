<?php

namespace Oro\Bundle\EntityBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Form\DataTransformer\EntityFieldFallbackTransformer;

class EntityFieldFallbackValueType extends AbstractType
{
    const NAME = 'oro_entity_field_fallback_value';

    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * EntityFieldFallbackValueType constructor.
     *
     * @param ConfigProvider $configProvider
     */
    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'value_type',
                'fallback_type',
                // entity of property holding the fallback value
                'parent_object',
                // translation prefix to generate fallback labels ex. oro.product.fallback
                'fallback_translation_prefix',
            ]
        );

        $resolver->setDefaults(
            [
                'fallback_options' => [],
                'use_fallback_options' => [],
                'value_options' => [],
                'data_class' => EntityFieldFallbackValue::class,
                'fallback_translation_prefix',
                'fallback_choice_filter' => null,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $valueOptions = array_merge(['required' => false], $options['value_options']);
        $fallbackOptions = $options['fallback_options'];
        $parentField = $builder->getName();
        if (!isset($fallbackOptions['choices']) || empty($fallbackOptions['choices'])) {
            $fallbackOptions['choices'] = $this->getFallbackOptions(
                $options['parent_object'],
                $parentField,
                $options['fallback_translation_prefix']
            );
        }

        // apply filter for fallback choices if defined
        if (is_callable($options['fallback_choice_filter'])) {
            $fallbackOptions['choices'] = call_user_func(
                $options['fallback_choice_filter'],
                $fallbackOptions['choices']
            );
        }

        $builder
            ->add('stringValue', $options['value_type'], $valueOptions)
            ->add(
                'useFallback',
                CheckboxType::class,
                array_merge(
                    [
                        'label' => 'oro.entity.fallback.use_fallback.label',
                        'required' => false,
                        'empty_data' => null,
                    ],
                    $options['use_fallback_options']
                )
            )
            ->add(
                'fallback',
                $options['fallback_type'],
                $fallbackOptions
            );

        $builder->addViewTransformer(new EntityFieldFallbackTransformer());
    }

    /**
     * @param object $parentObject
     * @param string $parentFieldName
     * @param string $labelPrefix
     *
     * @return array
     */
    protected function getFallbackOptions($parentObject, $parentFieldName, $labelPrefix)
    {
        $fallbackConfig = $this->configProvider->getConfig(get_class($parentObject), $parentFieldName)->getValues();
        $choices = [];
        foreach ($fallbackConfig as $fallbackKey => $fallbackField) {
            $labelSuffix = $fallbackKey;
            $choices[$fallbackKey] = $this->getCorrectFallbackLabel($labelPrefix, $labelSuffix);
        }

        return $choices;
    }

    /**
     * @param string $labelPrefix
     * @param string $labelSuffix
     *
     * @return string
     */
    protected function getCorrectFallbackLabel($labelPrefix, $labelSuffix)
    {
        $prefix = (substr($labelPrefix, -1) == '.') ? $labelPrefix : $labelPrefix . '.';

        return $prefix . $labelSuffix;
    }
}
