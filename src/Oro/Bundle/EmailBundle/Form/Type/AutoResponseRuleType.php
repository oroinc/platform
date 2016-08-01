<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\Type\OroEntityCreateOrSelectChoiceType;
use Oro\Bundle\EmailBundle\Validator\Constraints\AutoResponseRuleCondition as AutoResponseRuleConditionConstraint;

class AutoResponseRuleType extends AbstractType
{
    const NAME = 'oro_email_autoresponserule';

    /** @var EventSubscriberInterface */
    protected $autoResponseRuleSubscriber;

    /**
     * @param EventSubscriberInterface $autoResponseRuleSubscriber
     */
    public function __construct(EventSubscriberInterface $autoResponseRuleSubscriber)
    {
        $this->autoResponseRuleSubscriber = $autoResponseRuleSubscriber;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('active', 'choice', [
                'label' => 'oro.email.autoresponserule.status.label',
                'choices' => [
                    true  => 'oro.email.autoresponserule.status.active',
                    false => 'oro.email.autoresponserule.status.inactive',
                ]
            ])
            ->add('name', 'text', [
                'label' => 'oro.email.autoresponserule.name.label',
            ])
            ->add('conditions', 'oro_collection', [
                'label' => 'oro.email.autoresponserule.conditions.label',
                'type' => AutoResponseRuleConditionType::NAME,
                'options' => [
                    'constraints' => [
                        new AutoResponseRuleConditionConstraint(),
                    ],
                    'error_bubbling' => false,
                ],
                'handle_primary' => false,
                'allow_add_after' => true,
            ])
            ->add('template', OroEntityCreateOrSelectChoiceType::NAME, [
                'label' => 'oro.email.autoresponserule.template.label',
                'class' => 'Oro\Bundle\EmailBundle\Entity\EmailTemplate',
                'create_entity_form_type' => 'oro_email_autoresponse_template',
                'select_entity_form_type' => 'oro_email_autoresponse_template_choice',
                'editable' => true,
                'edit_route' => 'oro_email_autoresponserule_edittemplate',
            ]);

        $builder->addEventSubscriber($this->autoResponseRuleSubscriber);
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
