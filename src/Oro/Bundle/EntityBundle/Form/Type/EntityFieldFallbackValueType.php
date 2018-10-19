<?php

namespace Oro\Bundle\EntityBundle\Form\Type;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Exception\Fallback\FallbackFieldConfigurationMissingException;
use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityBundle\Form\DataTransformer\EntityFieldFallbackTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntityFieldFallbackValueType extends AbstractType
{
    const NAME = 'oro_entity_fallback_value';

    /**
     * @var EntityFallbackResolver
     */
    protected $fallbackResolver;

    /**
     * @param EntityFallbackResolver $fallbackResolver
     */
    public function __construct(EntityFallbackResolver $fallbackResolver)
    {
        $this->fallbackResolver = $fallbackResolver;
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
        $resolver->setDefined(
            [
                'value_type',
                'fallback_type',
            ]
        );

        $resolver->setDefaults(
            [
                'fallback_options' => [],
                'use_fallback_options' => [],
                'value_options' => [],
                'data_class' => EntityFieldFallbackValue::class
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addViewTransformer(new EntityFieldFallbackTransformer());

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $form = $event->getForm();
                $fallbackId = $event->getData() ? $event->getData()->getFallback() : null;

                $form->add(
                    'useFallback',
                    CheckboxType::class,
                    array_merge(
                        [
                            'label' => 'oro.entity.fallback.use_fallback.label',
                            'required' => false,
                            'empty_data' => null,
                            'mapped' => false,
                            'data' => isset($fallbackId)
                        ],
                        $form->getConfig()->getOption('use_fallback_options')
                    )
                );

                $valueType = $this->getValueFormType($form);
                $form->add(
                    'scalarValue',
                    $valueType,
                    $this->getValueFormOptions($form, $valueType)
                );

                $fallbackType = $this->getFallbackFormType($form);
                $form->add(
                    'fallback',
                    $fallbackType,
                    $this->getFallbackFormOptions($form, $fallbackType)
                );
            }
        );
    }

    /**
     * @param FormInterface $form
     * @return string
     */
    protected function getValueFormType(FormInterface $form)
    {
        // if developer specified type, just use it
        if ($type = $form->getConfig()->getOption('value_type')) {
            return $type;
        }

        // get system configuration form description if exists
        $formDescription = $this->fallbackResolver->getSystemConfigFormDescription(
            $form->getParent()->getData(),
            $form->getConfig()->getName()
        );
        if (isset($formDescription['type'])) {
            return $formDescription['type'];
        }

        // if no system configuration, try to get type from parent object field name fallback configuration
        try {
            $type = $this->fallbackResolver->getType($form->getParent()->getData(), $form->getConfig()->getName());
        } catch (FallbackFieldConfigurationMissingException $e) {
            return ChoiceType::class;
        }

        switch ($type) {
            case EntityFallbackResolver::TYPE_BOOLEAN:
                return ChoiceType::class;
            case EntityFallbackResolver::TYPE_INTEGER:
                return IntegerType::class;
            case EntityFallbackResolver::TYPE_DECIMAL:
                return NumberType::class;
            case EntityFallbackResolver::TYPE_STRING:
                return TextType::class;
            default:
                return ChoiceType::class;
        }
    }

    /**
     * @param FormInterface $form
     * @param mixed $valueType
     * @return array
     */
    protected function getValueFormOptions(FormInterface $form, $valueType)
    {
        // add some default options
        $valueOptions = array_merge(
            $this->getDefaultOptions($valueType),
            $form->getConfig()->getOptions()['value_options']
        );

        // get system configuration form configuration if exists
        $sysConfigFormDefinition = $this->fallbackResolver->getSystemConfigFormDescription(
            $form->getParent()->getData(),
            $form->getConfig()->getName()
        );

        if (empty($sysConfigFormDefinition) || !isset($sysConfigFormDefinition['options'])) {
            return $valueOptions;
        }

        return array_merge($sysConfigFormDefinition['options'], $valueOptions);
    }

    /**
     * @param FormInterface $form
     * @return mixed
     */
    protected function getFallbackFormType(FormInterface $form)
    {
        $type = $form->getConfig()->getOption('fallback_type');
        if (!isset($type)) {
            $type = ChoiceType::class;
        }

        return $type;
    }

    /**
     * @param FormInterface $form
     * @param mixed $fallbackType
     * @return array
     */
    protected function getFallbackFormOptions(FormInterface $form, $fallbackType)
    {
        // add some default options
        $fallbackOptions = array_merge(
            $this->getDefaultOptions($fallbackType),
            $form->getConfig()->getOption('fallback_options')
        );

        // if developer specified custom choices, return current options
        if (!in_array($fallbackType, [ChoiceType::class, 'choice']) || isset($fallbackOptions['choices'])) {
            return $fallbackOptions;
        }

        $choices = [];

        // Read fallback list of parent object
        $fallbackList = $this->fallbackResolver->getFallbackConfig(
            $form->getParent()->getData(),
            $form->getConfig()->getName(),
            EntityFieldFallbackValue::FALLBACK_LIST
        );

        // generate choices from fallback list
        foreach ($fallbackList as $fallbackId => $fallbackConfig) {
            if (!$this->fallbackResolver->isFallbackSupported(
                $form->getParent()->getData(),
                $form->getConfig()->getName(),
                $fallbackId
            )
            ) {
                continue;
            }

            $choices[$this->fallbackResolver->getFallbackLabel($fallbackId)] = $fallbackId;
        }
        $fallbackOptions['choices'] = $choices;

        return $fallbackOptions;
    }

    /**
     * @param mixed $formTypeName
     * @return array
     */
    protected function getDefaultOptions($formTypeName)
    {
        $options = ['required' => false];
        if (in_array($formTypeName, [ChoiceType::class, 'choice'])) {
            $options['placeholder'] = false;
        }

        return $options;
    }
}
