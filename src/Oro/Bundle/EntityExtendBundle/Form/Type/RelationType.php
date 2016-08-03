<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType as RelationTypeBase;

class RelationType extends AbstractType
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var Config */
    protected $config;

    /** @var FormFactory */
    protected $formFactory;

    /** @var TargetFieldType */
    protected $targetFieldType;

    /**
     * @param ConfigManager   $configManager
     * @param TargetFieldType $targetFieldType
     */
    public function __construct(ConfigManager $configManager, TargetFieldType $targetFieldType)
    {
        $this->configManager   = $configManager;
        $this->targetFieldType = $targetFieldType;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->config      = $this->configManager
            ->getProvider('extend')
            ->getConfigById($options['config_id']);
        $this->formFactory = $builder->getFormFactory();

        $builder->add(
            'target_entity',
            new TargetType($this->configManager, $options['config_id']),
            [
                'constraints' => [new Assert\NotBlank()]
            ]
        );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSubmitData']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmitData']);
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmitData(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();
        if (!$data) {
            $data = $form->getParent()->getData();
        }

        if ($this->config->get('owner') === ExtendScope::OWNER_CUSTOM) {
            $targetEntity = $this->getArrayValue($data, 'target_entity');
            $relationType = $this->config->getId()->getFieldType();

            if ($relationType == RelationTypeBase::MANY_TO_ONE) {
                $this->addTargetField(
                    $form,
                    'target_field',
                    $targetEntity,
                    $this->getArrayValue($data, 'target_field')
                );
            } else {
                $this->addTargetField(
                    $form,
                    'target_grid',
                    $targetEntity,
                    $this->getArrayValue($data, 'target_grid'),
                    'oro.entity_extend.form.relation.entity_data_fields',
                    true
                );
                $this->addTargetField(
                    $form,
                    'target_title',
                    $targetEntity,
                    $this->getArrayValue($data, 'target_title'),
                    'oro.entity_extend.form.relation.entity_info_title',
                    true
                );
                $this->addTargetField(
                    $form,
                    'target_detailed',
                    $targetEntity,
                    $this->getArrayValue($data, 'target_detailed'),
                    'oro.entity_extend.form.relation.entity_detailed',
                    true
                );
            }
        }

        if ($event->getName() == FormEvents::PRE_SUBMIT) {
            $form->getParent()->setData(array_merge($form->getParent()->getData(), $data));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'mapped' => false,
                'label'  => false
            ]
        );
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
        return 'oro_entity_relation_type';
    }

    /**
     * @param FormInterface $form
     * @param string        $name
     * @param string        $targetEntityClass
     * @param array|null    $data
     * @param string|null   $label
     * @param bool          $multiple
     */
    protected function addTargetField(
        FormInterface $form,
        $name,
        $targetEntityClass,
        $data = null,
        $label = null,
        $multiple = false
    ) {
        $options                = [];
        $options['constraints'] = [new Assert\NotBlank()];
        if ($label) {
            $options['label'] = $label;
        }
        if ($multiple) {
            $options['multiple'] = true;
        }

        $targetFieldType = $this->targetFieldType;
        $targetFieldType->setEntityClass($targetEntityClass);

        $form->add(
            $this->formFactory->createNamed(
                $name,
                $targetFieldType,
                $data,
                $options
            )
        );
    }

    /**
     * @param array  $data
     * @param string $key
     * @param mixed  $defaultValue
     * @return mixed
     */
    protected function getArrayValue(array &$data, $key, $defaultValue = null)
    {
        return isset($data[$key])
            ? $data[$key]
            : $defaultValue;
    }
}
