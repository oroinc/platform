<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

use Oro\Bundle\EmailBundle\Entity\Email;

class AutoResponseTemplateChoiceType extends AbstractType
{
    const NAME = 'oro_email_autoresponse_template_choice';

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
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('entityName', 'hidden', [
                'mapped' => false,
                'data' => Email::ENTITY_CLASS,
                'constraints' => [
                    new Assert\IdenticalTo([
                        'value' => Email::ENTITY_CLASS
                    ])
                ]
            ])
            ->add('template', 'oro_email_template_list', [
                'label' => false,
                'configs' => [
                    'allowClear'  => true,
                    'placeholder' => 'oro.form.custom_value',
                ]
            ]);

        $builder->addEventSubscriber($this->templateFormSubscriber);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
