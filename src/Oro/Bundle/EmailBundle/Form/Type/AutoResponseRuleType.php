<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\OroEntityCreateOrSelectChoiceType;
use Oro\Bundle\QueryDesignerBundle\Form\Type\AbstractQueryDesignerType;

class AutoResponseRuleType extends AbstractQueryDesignerType
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
        parent::buildForm($builder, $options);

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
            ->add('template', OroEntityCreateOrSelectChoiceType::NAME, [
                'label' => 'oro.email.autoresponserule.template.label',
                'class' => 'Oro\Bundle\EmailBundle\Entity\EmailTemplate',
                'create_entity_form_type' => 'oro_email_autoresponse_template',
                'select_entity_form_type' => 'oro_email_autoresponse_template_choice',
                'editable' => true,
                'edit_route' => 'oro_email_autoresponserule_edittemplate',
            ])
            // this field represents selected entity in query builder entity selector
            ->add('entity', 'hidden', [
                'mapped' => false,
                'data' => 'email',
            ]);

        $builder->addEventSubscriber($this->autoResponseRuleSubscriber);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => 'Oro\Bundle\EmailBundle\Entity\AutoResponseRule',
            'query_type' => 'string',
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
