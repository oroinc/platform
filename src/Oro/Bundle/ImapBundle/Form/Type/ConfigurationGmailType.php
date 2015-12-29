<?php

namespace Oro\Bundle\ImapBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ImapBundle\Mail\Storage\GmailImap;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConfigurationGmailType extends AbstractType
{
    const NAME = 'oro_imap_configuration_gmail';

    /** @var TranslatorInterface */
    protected $translator;

    /** ConfigManager */
    protected $userConfigManager;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(
        TranslatorInterface $translator,
        ConfigManager $userConfigManager
    ) {
        $this->translator = $translator;
        $this->userConfigManager = $userConfigManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('check', 'button', [
                'label' => $this->translator->trans('oro.imap.configuration.connect'),
                'attr' => ['class' => 'btn btn-primary']
            ])
            ->add('accessToken', 'hidden')
            ->add('imapHost', 'hidden', [
                'label'    => 'oro.imap.configuration.imap_host.label',
                'required' => true,
                'data' => GmailImap::DEFAULT_GMAIL_HOST
            ])
            ->add('imapPort', 'hidden', [
                'required' => true,
                'data' => GmailImap::DEFAULT_GMAIL_PORT
            ])
            ->add('user', 'hidden', [
                'required' => true,
            ])
            ->add('clientId', 'hidden', [
                'data' => $this->userConfigManager->get('oro_google_integration.client_id')
            ]);

        $this->initEvents($builder);
    }

    /**
     * @param FormEvent $formEvent
     */
    public function preSubmit(FormEvent $formEvent)
    {
        $form = $formEvent->getForm();
        $emailOrigin = $form->getData();

        if ($emailOrigin instanceof UserEmailOrigin) {
            $this->updateForm($form, $emailOrigin);
        }
    }

    /**
     * @param FormEvent $formEvent
     */
    public function preSetData(FormEvent $formEvent)
    {
        $form = $formEvent->getForm();
        $emailOrigin = $formEvent->getData();

        if ($emailOrigin instanceof UserEmailOrigin) {
            $this->updateForm($form, $emailOrigin);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Oro\\Bundle\\ImapBundle\\Entity\\UserEmailOrigin'
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param FormBuilderInterface $builder
     */
    protected function initEvents(FormBuilderInterface $builder)
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
    }

    /**
     * @param FormInterface $form
     * @param UserEmailOrigin $emailOrigin
     */
    protected function updateForm(FormInterface $form, UserEmailOrigin $emailOrigin)
    {
        if (!empty($emailOrigin->getAccessToken())) {
            $form->add('checkFolder', 'button', [
                'label' => $this->translator->trans('oro.email.retrieve_folders.label'),
                'attr' => ['class' => 'btn btn-primary']
            ])
                ->add('folders', 'oro_email_email_folder_tree', [
                'label' => $this->translator->trans('oro.email.folders.label'),
                'attr' => ['class' => 'folder-tree'],
                'tooltip' => $this->translator->trans('oro.email.folders.tooltip'),
            ])
                ->add('mailboxName', 'hidden', [
                'data' => 'Local'
            ]);

            $form->remove('check');
        }
    }
}
