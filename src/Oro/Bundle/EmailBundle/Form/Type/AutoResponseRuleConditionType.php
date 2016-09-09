<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;

class AutoResponseRuleConditionType extends AbstractType
{
    const NAME = 'oro_email_autoresponserule_condition';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('field', 'choice', [
                'choices' => [
                    'subject'   => 'oro.email.subject.label',
                    'emailBody.bodyContent' => 'oro.email.email_body.label',
                    'fromName'  => 'From',
                    'cc.__index__.name'  => 'Cc',
                    'bcc.__index__.name' => 'Bcc',
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
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return static::NAME;
    }
}
