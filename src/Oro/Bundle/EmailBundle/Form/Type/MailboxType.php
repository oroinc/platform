<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotNull;

use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Provider\MailboxProcessorProvider;
use Oro\Bundle\FormBundle\Utils\FormUtils;

class MailboxType extends AbstractType
{
    const RELOAD_MARKER = '_reloadForm';

    /** @var MailboxProcessorProvider */
    private $processorProvider;

    /**
     * @param MailboxProcessorProvider $processorProvider
     */
    public function __construct(MailboxProcessorProvider $processorProvider)
    {
        $this->processorProvider = $processorProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class'         => 'Oro\Bundle\EmailBundle\Entity\Mailbox',
            'cascade_validation' => true,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('label', 'text', [
            'required'    => true,
            'label'       => 'oro.email.mailbox.label.label',
            'constraints' => [
                new NotNull(),
            ],
        ]);
        $builder->add('email', 'email', [
            'required'    => true,
            'label'       => 'oro.email.mailbox.email.label',
            'constraints' => [
                new NotNull(),
            ],
        ]);
        $builder->add('originEnable', 'checkbox', [
            'required' => false,
            'label'    => 'oro.email.mailbox.imap_enable.label',
            'data'     => true,
            'mapped'   => false,
        ]);
        $builder->add('origin', 'oro_imap_configuration');
        $builder->add('smtpSettings', 'oro_email_smtp');
        $builder->add('processorType', 'choice', [
            'label'       => 'oro.email.mailbox.processor.type.label',
            'choices'     => $this->processorProvider->getProcessorTypesChoiceList(),
            'required'    => false,
            'mapped'      => false,
            'empty_value' => 'oro.email.mailbox_processor.default.label',
        ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSet']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_email_mailbox';
    }

    /**
     * PreSet event handler.
     *
     * @param FormEvent $event
     */
    public function preSet(FormEvent $event)
    {
        /** @var Mailbox $data */
        $data = $event->getData();
        $form = $event->getForm();

        if ($data === null) {
            return;
        }

        $processorType = null;
        if ($processorEntity = $data->getProcessor()) {
            $processorType = $processorEntity->getType();
        }

        FormUtils::replaceField($form, 'processorType', [
            'data' => $processorType,
        ]);

        $this->addProcessorField($form, $processorType);
    }

    /**
     * PreSubmit event handler.
     *
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        $processorType = $data['processorType'];
        $originalProcessorType = $form->get('processorType')->getData();

        if ($processorType !== $originalProcessorType) {
            $form->getViewData()->clearProcessor();
        }

        $this->addProcessorField($form, $processorType);
    }

    /**
     * Adds mailbox processor form field of proper type
     *
     * @param FormInterface $form
     * @param string|null   $processorType
     */
    protected function addProcessorField(FormInterface $form, $processorType)
    {
        if (!empty($processorType)) {
            $form->add(
                'processor',
                $this->processorProvider->getProcessorTypes()[$processorType]->getSettingsFormType(),
                [
                    'required' => true,
                ]
            );
        } else {
            $form->add(
                'processor',
                'hidden',
                [
                    'data' => null,
                ]
            );
        }
    }
}
