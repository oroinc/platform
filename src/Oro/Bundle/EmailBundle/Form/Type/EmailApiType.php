<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Oro\Bundle\EmailBundle\Form\Model\EmailApi;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\SoapBundle\Form\EventListener\PatchSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

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
                CollectionType::class,
                [
                    'required'       => false,
                    'allow_add'      => true,
                    'entry_type'     => EmailFolderApiType::class,
                    'error_bubbling' => false
                ]
            )
            ->add('from', EmailAddressApiType::class, ['required' => false])
            ->add('to', EmailAddressApiType::class, ['required' => false, 'multiple' => true])
            ->add('cc', EmailAddressApiType::class, ['required' => false, 'multiple' => true])
            ->add('bcc', EmailAddressApiType::class, ['required' => false, 'multiple' => true])
            ->add(
                'subject',
                TextType::class,
                [
                    'required'    => false,
                    'constraints' => [
                        new Assert\Length(['max' => 500])
                    ]
                ]
            )
            ->add('body', TextType::class, ['required' => false])
            ->add(
                'bodyType',
                HiddenType::class,
                ['required' => false, 'data_transformer' => 'oro_email.email_body_type_transformer']
            )
            ->add('createdAt', OroDateTimeType::class, ['required' => false])
            ->add('sentAt', OroDateTimeType::class, ['required' => false])
            ->add('receivedAt', OroDateTimeType::class, ['required' => false])
            ->add('internalDate', OroDateTimeType::class, ['required' => false])
            ->add(
                'importance',
                HiddenType::class,
                ['required' => false, 'data_transformer' => 'oro_email.email_importance_transformer']
            )
            ->add(
                'head',
                ChoiceType::class,
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
                ChoiceType::class,
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
                TextType::class,
                [
                    'required'    => false,
                    'constraints' => [
                        new Assert\Length(['max' => 255])
                    ]
                ]
            )
            ->add(
                'xMessageId',
                TextType::class,
                [
                    'required'    => false,
                    'constraints' => [
                        new Assert\Length(['max' => 255])
                    ]
                ]
            )
            ->add(
                'xThreadId',
                TextType::class,
                [
                    'required'    => false,
                    'constraints' => [
                        new Assert\Length(['max' => 255])
                    ]
                ]
            )
            ->add(
                'thread',
                EntityIdentifierType::class,
                [
                    'required'       => false,
                    'class'          => 'OroEmailBundle:EmailThread',
                    'multiple'       => false,
                    'error_bubbling' => false
                ]
            )
            ->add('refs', TextType::class, ['required' => false]);

        $builder->addEventSubscriber(new PatchSubscriber());
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'postSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmit']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'           => 'Oro\Bundle\EmailBundle\Form\Model\EmailApi',
                'csrf_token_id'        => 'email',
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
