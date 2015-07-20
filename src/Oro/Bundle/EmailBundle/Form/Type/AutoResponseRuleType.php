<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

class AutoResponseRuleType extends AbstractType
{
    const NAME = 'oro_email_autoresponserule';

    /** @var EventSubscriberInterface */
    protected $templateFormSubscriber;

    /**
     * @param EventSubscriberInterface $templateFormSubscriber
     */
    public function __construct(EventSubscriberInterface $templateFormSubscriber)
    {
        $this->templateFormSubscriber = $templateFormSubscriber;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('entityName', 'hidden', [
                'mapped' => false,
                'data' => 'Oro\Bundle\EmailBundle\Entity\Email',
                'constraints' => [
                    new Assert\IdenticalTo([
                        'value' => 'Oro\Bundle\EmailBundle\Entity\Email'
                    ])
                ]
            ])
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
            ->add('template', 'oro_email_template_list', [
                'label' => 'oro.email.autoresponserule.template.label',
            ]);

        $builder->addEventSubscriber($this->templateFormSubscriber);
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
