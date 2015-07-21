<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\Type\OroEntityCreateOrSelectChoiceType;

class AutoResponseRuleType extends AbstractType
{
    const NAME = 'oro_email_autoresponserule';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('active', 'checkbox', [
                'label' => 'oro.email.autoresponserule.active.label',
            ])
            ->add('name', 'text', [
                'label' => 'oro.email.autoresponserule.name.label',
            ])
            ->add('conditions', 'oro_collection', [
                'label' => 'oro.email.autoresponserule.conditions.label',
                'type' => AutoResponseRuleConditionType::NAME,
                'handle_primary' => false,
                'allow_add_after' => true,
            ])
            ->add('template', OroEntityCreateOrSelectChoiceType::NAME, [
                'label' => 'oro.email.autoresponserule.template.label',
                'class' => 'Oro\Bundle\EmailBundle\Entity\EmailTemplate',
                'create_entity_form_type' => 'oro_email_autoresponse_template',
                'select_entity_form_type' => 'oro_email_autoresponse_template_choice',
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Oro\Bundle\EmailBundle\Entity\AutoResponseRule',
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
