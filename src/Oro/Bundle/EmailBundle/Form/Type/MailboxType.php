<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotNull;

use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Mailbox\MailboxProcessStorage;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;

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
    public function configureOptions(OptionsResolver $resolver)
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
        $builder->add('email', 'oro_email_email_address', [
            'required'    => true,
            'label'       => 'oro.email.mailbox.email.label',
            'constraints' => [
                new NotNull(),
                new Email(),
            ],
        ]);
        $builder->add('origin', 'oro_imap_configuration');
        $builder->add('processType', 'choice', [
            'label'       => 'oro.email.mailbox.process.type.label',
            'choices'     => $this->storage->getProcessTypeChoiceList(),
            'required'    => false,
            'mapped'      => false,
            'empty_value' => 'oro.email.mailbox.process.default.label',
            'empty_data'  => null,
        ]);
        $builder->add(
            'authorizedUsers',
            'oro_user_organization_acl_multiselect',
            [
                'label' => 'oro.user.entity_plural_label',
            ]
        );
        $builder->add(
            'authorizedRoles',
            'oro_role_multiselect',
            [
                'label' => 'oro.user.role.entity_plural_label',
            ]
        );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSet']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmit']);
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
     * Adds appropriate process field to form based on set value.
     *
     * @param FormEvent $event
     */
    public function preSet(FormEvent $event)
    {
        /** @var Mailbox $data */
        $data = $event->getData();
        $form = $event->getForm();

        FormUtils::replaceField(
            $form,
            'processType',
            [
                'choices' => $this->storage->getProcessTypeChoiceList($data)
            ]
        );

        if ($data === null) {
            return;
        }

        $processType = null;
        if (null !== $processEntity = $data->getProcessSettings()) {
            $processType = $processEntity->getType();
        }

        FormUtils::replaceField($form, 'processType', ['data' => $processType]);

        $this->addProcessField($form, $processType);
    }

    /**
     * PreSubmit event handler.
     *
     * If process type is changed ... replace with proper form type and set process type to null.
     *
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        $processType = isset($data['processType']) ? $data['processType'] : null;
        $originalProcessType = $form->get('processType')->getData();

        if ($processType !== $originalProcessType) {
            $mailbox = $form->getData();
            if ($mailbox) {
                $mailbox->setProcessSettings(null);
            }

            if (!$processType) {
                $data['processSettings'] = null;
                $event->setData($data);
            }
        }

        $this->addProcessField($form, $processType);
    }

    /**
     * PostSubmit event handler.
     *
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        /** @var Mailbox $data */
        $data = $event->getData();
        if ($data !== null) {
            /** @var UserEmailOrigin $origin */
            $origin = $data->getOrigin();
            $currentDate = new \DateTime('now', new \DateTimeZone('UTC'));
            $origin->setSynchronizedAt($currentDate);
            foreach ($origin->getFolders() as $folder) {
                if ($folder->isSyncEnabled()) {
                    $folder->setSynchronizedAt($currentDate);
                }
            }
            $event->setData($data);
        }
    }

    /**
     * Adds mailbox process form field of proper type
     *
     * @param FormInterface $form
     * @param string|null   $processType
     */
    protected function addProcessField(FormInterface $form, $processType)
    {
        if (!empty($processType)) {
            $process = $this->storage->getProcess($processType);
            if ($process->isEnabled()) {
                $form->add(
                    'processSettings',
                    $this->storage->getProcess($processType)->getSettingsFormType(),
                    [
                        'required' => true,
                    ]
                );

                return;
            }
        }

        $form->add(
            'processSettings',
            'hidden',
            [
                'data' => null,
            ]
        );
    }
}
