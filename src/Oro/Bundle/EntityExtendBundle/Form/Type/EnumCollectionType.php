<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\OptionSet;

class EnumCollectionType extends AbstractType
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager  = $configManager;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmitData']);
    }

    /**
     * @param FormEvent $event
     */
    public function postSubmitData(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        /** @var FieldConfigModel $configModel */
        $configModel = $form->getRoot()->getConfig()->getOptions()['config_model'];

        if (empty($data)) {
            return;
        }

        // TODO: implement when we'll have EnumValue
        return;

        $em           = $this->configManager->getEntityManager();
        $optionValues = $oldOptions = $configModel->getOptions()->getValues();
        array_walk_recursive(
            $oldOptions,
            function (&$oldOption) {
                $oldOption = $oldOption->getId();
            }
        );

        $newOptions   = [];
        foreach ($data as $option) {
            if (is_array($option)) {
                $optionSet = new OptionSet();
                $optionSet->setField($configModel);
                $optionSet->setData(
                    $option['id'],
                    $option['priority'],
                    $option['label'],
                    (bool)$option['default']
                );
            } elseif (!$option->getId()) {
                $optionSet = $option;
                $optionSet->setField($configModel);
            } else {
                $optionSet = $option;
            }

            if ($optionSet->getLabel() != null) {
                $newOptions[] = $optionSet->getId();
            }
            if (!in_array($optionSet, $optionValues) && $optionSet->getLabel() != null) {
                $em->persist($optionSet);
            }
        }

        $delOptions = array_diff($oldOptions, $newOptions);
        foreach (array_keys($delOptions) as $key) {
            $em->remove($configModel->getOptions()->getValues()[$key]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'allow_add'            => true,
                'allow_delete'         => true,
                'by_reference'         => false,
                'prototype'            => true,
                'prototype_name'       => '__name__',
                'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
                'show_form_when_empty' => true
            ]
        );
        $resolver->setRequired(['type']);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars = array_replace(
            $view->vars,
            [
                'show_form_when_empty' => $options['show_form_when_empty']
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'collection';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_entity_enum_collection';
    }
}
