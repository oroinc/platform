<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class AutoResponseRuleConditionType extends AbstractType
{
    const NAME = 'oro_email_autoresponserule_condition';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('operation', 'choice', [
                'choices' => [
                    FilterUtility::CONDITION_AND => FilterUtility::CONDITION_AND,
                    FilterUtility::CONDITION_OR  => FilterUtility::CONDITION_OR,
                ],
            ])
            ->add('field', 'choice', [
                'choices' => [
                    'subject'   => 'oro.email.subject.label',
                    'emailBody' => 'oro.email.email_body.label',
                ],
            ])
            ->add('filter', TextFilterType::NAME, [
                'inherit_data' => true,
                'operator_options' => [
                    'empty_value' => false,
                    'property_path' => 'filterType',
                ],
                'field_options' => [
                    'property_path' => 'filterValue',
                ],
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Oro\Bundle\EmailBundle\Entity\AutoResponseRuleCondition',
            'sortable' => true,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
