<?php

namespace Oro\Bundle\EntityBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Form\DataTransformer\EntityFieldFallbackTransformer;

class EntityFieldFallbackValueType extends AbstractType
{
    const NAME = 'oro_entity_fallback_value';

    /**
     * @var EntityFallbackResolver
     */
    protected $fallbackResolver;

    /**
     * EntityFieldFallbackValueType constructor.
     *
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
        $resolver->setRequired(
            [
                // translation prefix to generate fallback labels ex. oro.entity.fallback
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
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
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
            );

        $builder->addViewTransformer(new EntityFieldFallbackTransformer());

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $form = $event->getForm();

                $form->add(
                    'viewValue',
                    $this->getValueFormType($form),
                    $this->getValueFormOptions($form)
                );

                $form->add(
                    'fallback',
                    $this->getFallbackFormType($form),
                    $this->getFallbackFormOptions($form)
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
        $type = $this->fallbackResolver->getType($form->getParent()->getData(), $form->getConfig()->getName());

        switch ($type) {
            case EntityFallbackResolver::TYPE_BOOLEAN:
                return ChoiceType::class;
            case EntityFallbackResolver::TYPE_INTEGER:
                return IntegerType::class;
            case EntityFallbackResolver::TYPE_STRING:
                return TextType::class;
            default:
                return ChoiceType::class;
        }
    }

    /**
     * @param FormInterface $form
     * @return array
     */
    protected function getValueFormOptions(FormInterface $form)
    {
        // add some default options
        $valueOptions = array_merge(
            ['required' => false, 'empty_value' => false],
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
     * @return array
     */
    protected function getFallbackFormOptions(FormInterface $form)
    {
        // add some default options
        $fallbackOptions = array_merge(
            ['required' => false, 'empty_value' => false],
            $form->getConfig()->getOption('fallback_options')
        );

        // if developer specified custom choices, return current options
        if (isset($fallbackOptions['choices'])) {
            return $fallbackOptions;
        }

        $labelPrefix = $form->getConfig()->getOption('fallback_translation_prefix');
        $choices = [];

        // Read fallback list of parent object
        $fallbackList = $this->fallbackResolver->getFallbackConfig(
            $form->getParent()->getData(),
            $form->getConfig()->getName(),
            EntityFieldFallbackValue::FALLBACK_LIST_KEY
        );

        // generate choices from fallback list
        foreach ($fallbackList as $fallbackId => $fallbackField) {
            if (!$this->fallbackResolver->isFallbackSupported(
                $form->getParent()->getData(),
                $form->getConfig()->getName(),
                $fallbackId
            )
            ) {
                continue;
            }

            $labelSuffix = $fallbackId;
            $choices[$fallbackId] = $this->getCorrectFallbackLabel($labelPrefix, $labelSuffix);
        }
        $fallbackOptions['choices'] = $choices;

        return $fallbackOptions;
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
