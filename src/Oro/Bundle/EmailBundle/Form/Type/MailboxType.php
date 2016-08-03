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
use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class MailboxType extends AbstractType
{
    const RELOAD_MARKER = '_reloadForm';

    /** @var MailboxProcessStorage */
    private $storage;

    /** @var Mcrypt */
    protected $encryptor;

    /** ConfigManager */
    protected $userConfigManager;

    /**
     * @param MailboxProcessStorage $storage
     * @param Mcrypt $encryptor
     * @param ConfigManager $userConfigManager
     */
    public function __construct(
        MailboxProcessStorage $storage,
        Mcrypt $encryptor,
        ConfigManager $userConfigManager
    ) {
        $this->storage = $storage;
        $this->encryptor = $encryptor;
        $this->userConfigManager = $userConfigManager;
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

        if ($this->userConfigManager->get('oro_imap.enable_google_imap')) {
            $builder->add('imapAccountType', 'oro_imap_choice_account_type');
        } else {
            $builder->add('origin', 'oro_imap_configuration');
        }

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
            'oro_user_multiselect',
            [
                'label' => 'oro.user.entity_plural_label',
            ]
        );
        $builder->add(
            'authorizedRoles',
            'oro_role_multiselect',
            [
                'autocomplete_alias' => 'roles_authenticated',
                'label' => 'oro.user.role.entity_plural_label',
            ]
        );
        $builder->add('passwordHolder', 'hidden', [
            'required' => false,
            'label' => '',
            'mapped' => false
        ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSet']);
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'postSet']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmit']);
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

        /*
         * Process types are selected based on mailbox, some processes are for example not available in some
         * organizations.
         */
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

        /*
         * If data has already selected some kind of process type, make it default value for field.
         */
        $processType = null;
        if (null !== $processEntity = $data->getProcessSettings()) {
            $processType = $processEntity->getType();
        }
        FormUtils::replaceField($form, 'processType', ['data' => $processType]);

        /*
         * Add appropriate field for selected process type to form.
         */
        $this->addProcessField($form, $processType);

        /*
         * Configure user field to display only users from organization which mailbox belongs to.
         */
        $this->configureUserField($form, $data);
    }

    /**
     * Set password on form reload
     *
     * @param FormEvent $event
     */
    public function postSet(FormEvent $event)
    {
        /** @var Mailbox $data */
        $data = $event->getData();
        $form = $event->getForm();

        if ($data instanceof Mailbox && $data->getOrigin() && $form->has('passwordHolder')) {
            $form->get('passwordHolder')->setData(
                $this->encryptor->decryptData($data->getOrigin()->getPassword())
            );
        }
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
     * Form post submit handler.
     *
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        $data = $event->getData();

        $this->setOriginOrganizationAndOwner($data);
        $this->setFolderStartSync($data);
    }

    /**
     * Set folder start sync date to prevent sync old emails
     *
     * @param Mailbox $data
     */
    protected function setFolderStartSync(Mailbox $data = null)
    {
        /* @var $origin UserEmailOrigin */
        if (!$data || !$origin = $data->getOrigin()) {
            return;
        }

        $currentDate = new \DateTime('now', new \DateTimeZone('UTC'));
        foreach ($origin->getFolders() as $folder) {
            if ($folder->isSyncEnabled()) {
                $folder->setSyncStartDate($currentDate);
            }
        }
    }

    /**
     * Sets proper organization to origin. Set owner to null.
     *
     * @param Mailbox $data
     */
    protected function setOriginOrganizationAndOwner(Mailbox $data = null)
    {
        if ($data !== null) {
            $organization = $data->getOrganization();

            if (null !== $origin = $data->getOrigin()) {
                if (null !== $origin->getOwner()) {
                    $origin->setOwner(null);
                }
                $origin->setOrganization($organization);
            }
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

    /**
     * Configures user field so it searches only within mailboxes' organization.
     *
     * @param FormInterface $form
     * @param Mailbox       $data
     */
    protected function configureUserField(FormInterface $form, Mailbox $data)
    {
        if (!$data->getOrganization()) {
            return;
        }

        FormUtils::replaceField(
            $form,
            'authorizedUsers',
            [
                'configs'            => [
                    'route_name'              => 'oro_email_mailbox_users_search',
                    'route_parameters'        => ['organizationId' => $data->getOrganization()->getId()],
                    'multiple'                => true,
                    'width'                   => '400px',
                    'placeholder'             => 'oro.user.form.choose_user',
                    'allowClear'              => true,
                    'result_template_twig'    => 'OroUserBundle:User:Autocomplete/result.html.twig',
                    'selection_template_twig' => 'OroUserBundle:User:Autocomplete/selection.html.twig',
                ]
            ]
        );
    }
}
