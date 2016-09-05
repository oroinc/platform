<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Oro\Bundle\EmailBundle\Form\Model\EmailApi;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

use Oro\Bundle\SoapBundle\Form\EventListener\PatchSubscriber;

class EmailApiType extends AbstractType
{
    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'folders',
                'collection',
                [
                    'required'       => false,
                    'allow_add'      => true,
                    'type'           => 'oro_email_email_folder_api',
                    'error_bubbling' => false
                ]
            )
            ->add('from', 'oro_email_email_address_api', ['required' => false])
            ->add('to', 'oro_email_email_address_api', ['required' => false, 'multiple' => true])
            ->add('cc', 'oro_email_email_address_api', ['required' => false, 'multiple' => true])
            ->add('bcc', 'oro_email_email_address_api', ['required' => false, 'multiple' => true])
            ->add(
                'subject',
                'text',
                [
                    'required'    => false,
                    'constraints' => [
                        new Assert\Length(['max' => 500])
                    ]
                ]
            )
            ->add('body', 'text', ['required' => false])
            ->add(
                'bodyType',
                'hidden',
                ['required' => false, 'data_transformer' => 'oro_email.email_body_type_transformer']
            )
            ->add('createdAt', 'oro_datetime', ['required' => false])
            ->add('sentAt', 'oro_datetime', ['required' => false])
            ->add('receivedAt', 'oro_datetime', ['required' => false])
            ->add('internalDate', 'oro_datetime', ['required' => false])
            ->add(
                'importance',
                'hidden',
                ['required' => false, 'data_transformer' => 'oro_email.email_importance_transformer']
            )
            ->add(
                'head',
                'choice',
                [
                    'required' => false,
                    'choices'  => [
                        0 => false,
                        1 => true
                    ]
                ]
            )
            ->add(
                'seen',
                'choice',
                [
                    'required' => false,
                    'choices'  => [
                        0 => false,
                        1 => true
                    ]
                ]
            )
            ->add(
                'messageId',
                'text',
                [
                    'required'    => false,
                    'constraints' => [
                        new Assert\Length(['max' => 255])
                    ]
                ]
            )
            ->add(
                'xMessageId',
                'text',
                [
                    'required'    => false,
                    'constraints' => [
                        new Assert\Length(['max' => 255])
                    ]
                ]
            )
            ->add(
                'xThreadId',
                'text',
                [
                    'required'    => false,
                    'constraints' => [
                        new Assert\Length(['max' => 255])
                    ]
                ]
            )
            ->add(
                'thread',
                'oro_entity_identifier',
                [
                    'required'       => false,
                    'class'          => 'OroEmailBundle:EmailThread',
                    'multiple'       => false,
                    'error_bubbling' => false
                ]
            )
            ->add('refs', 'text', ['required' => false]);

        $builder->addEventSubscriber(new PatchSubscriber());
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'postSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmit']);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'           => 'Oro\Bundle\EmailBundle\Form\Model\EmailApi',
                'intention'            => 'email',
                'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
                'cascade_validation'   => true,
                'csrf_protection'      => false
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
        return 'oro_email_email_api';
    }

    /**
     * POST_SET_DATA event handler
     *
     * @param FormEvent $event
     */
    public function postSetData(FormEvent $event)
    {
        /** @var EmailApi $data */
        $data = $event->getData();
        if (!$data || ($data->getEntity() && $data->getEntity()->getId())) {
            return;
        }

        $form = $event->getForm();

        FormUtils::replaceField(
            $form,
            'folders',
            [
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ]
        );
        FormUtils::replaceField(
            $form,
            'messageId',
            [
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ]
        );
        FormUtils::replaceField(
            $form,
            'from',
            [
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ]
        );
    }

    /**
     * POST_SUBMIT event handler
     *
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        $data = $event->getData();
        if (!$data || ($data->getEntity() && $data->getEntity()->getId())) {
            return;
        }

        if (!$data->getTo() && !$data->getCc() && !$data->getBcc()) {
            $event->getForm()->addError(new FormError('Recipients should not be empty'));
        }
    }
}
