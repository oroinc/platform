<?php

namespace Oro\Bundle\EntityBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\ExecutionContextInterface;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Used for validation and automatic identifier setter
 */
class EnumFormType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::SUBMIT,
            function (FormEvent $event) {
                /** @var AbstractEnumValue $enum */
                $enum = $event->getData();

                if (!$enum->getId() && $enum->getName()) {
                    // set enum id, if it's empty, re-create enum as it has no setId method
                    $dataClass = $event->getForm()->getConfig()->getDataClass();
                    $enum = new $dataClass(
                        ExtendHelper::buildEnumCode($enum->getName()),
                        $enum->getName(),
                        $enum->getPriority(),
                        $enum->isDefault()
                    );
                    $event->setData($enum);
                }
            }
        );
    }

    /**
     * @param AbstractEnumValue         $enumValue
     * @param ExecutionContextInterface $context
     */
    public static function isFormValid(AbstractEnumValue $enumValue, ExecutionContextInterface $context)
    {
        $name = $enumValue->getName();
        if (empty($name)) {
            $context->addViolationAt('name', 'Name is required');
        }

        $priority = $enumValue->getPriority();
        if (!is_int($priority)) {
            $context->addViolationAt('priority', 'Priority is required');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'custom_enum_type';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'custom_entity_type';
    }
}
