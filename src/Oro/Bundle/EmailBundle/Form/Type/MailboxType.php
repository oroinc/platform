<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Oro\Bundle\EmailBundle\Mailbox\MailboxProcessStorage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotNull;

use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\FormBundle\Utils\FormUtils;

class MailboxType extends AbstractType
{
    const RELOAD_MARKER = '_reloadForm';

    /** @var MailboxProcessStorage */
    private $storage;

    /**
     * @param MailboxProcessStorage $storage
     */
    public function __construct(MailboxProcessStorage $storage)
    {
        $this->storage = $storage;
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
        $builder->add('processType', 'choice', [
            'label'       => 'oro.email.mailbox.process.type.label',
            'choices'     => $this->storage->getProcessTypeChoiceList(),
            'required'    => false,
            'mapped'      => false,
            'empty_value' => 'oro.email.mailbox_process.default.label',
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

        $processType = null;
        if ($processEntity = $data->getProcessSettings()) {
            $processType = $processEntity->getType();
        }

        FormUtils::replaceField($form, 'processType', [
            'data' => $processType,
        ]);

        $this->addProcessField($form, $processType);
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

        $processorType = $data['processType'];
        $originalProcessType = $form->get('processType')->getData();

        if ($processorType !== $originalProcessType) {
            $form->getViewData()->setProcessSettings(null);
        }

        $this->addProcessField($form, $processorType);
    }

    /**
     * Adds mailbox processor form field of proper type
     *
     * @param FormInterface $form
     * @param string|null   $processType
     */
    protected function addProcessField(FormInterface $form, $processType)
    {
        if (!empty($processType)) {
            $form->add(
                'processSettings',
                $this->storage->getProcess($processType)->getSettingsFormType(),
                [
                    'required' => true,
                ]
            );
        } else {
            $form->add(
                'processSettings',
                'hidden',
                [
                    'data' => null,
                ]
            );
        }
    }
}
