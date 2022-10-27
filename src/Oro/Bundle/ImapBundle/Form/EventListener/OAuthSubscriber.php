<?php

namespace Oro\Bundle\ImapBundle\Form\EventListener;

use Oro\Bundle\EmailBundle\Form\Type\EmailFolderTreeType;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Manager\OAuthManagerRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Default OAuth subscriber. Adds folder tree and check button
 * to OAuth-aware forms
 */
class OAuthSubscriber implements EventSubscriberInterface
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var OAuthManagerRegistry */
    protected $oauthManagerRegistry;

    public function __construct(TranslatorInterface $translator, OAuthManagerRegistry $oauthManagerRegistry)
    {
        $this->translator = $translator;
        $this->oauthManagerRegistry = $oauthManagerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SUBMIT    => 'setToken',
            FormEvents::PRE_SET_DATA  => 'extendForm',
            FormEvents::POST_SET_DATA => 'disableFoldersButton'
        ];
    }

    public function setToken(FormEvent $formEvent)
    {
        $form = $formEvent->getForm();
        /** @var UserEmailOrigin $emailOrigin */
        $emailOrigin = $form->getData();

        if (null === $emailOrigin || null === $emailOrigin->getAccessToken()) {
            $data = $formEvent->getData();
            if (null === $data || !isset($data['accessToken'])) {
                return;
            }
            $emailOrigin = new UserEmailOrigin();
            $emailOrigin->setAccessToken($data['accessToken']);
        }

        if ($emailOrigin instanceof UserEmailOrigin) {
            $this->updateForm($form, $emailOrigin);
        }
    }

    public function extendForm(FormEvent $formEvent)
    {
        $form = $formEvent->getForm();
        $emailOrigin = $formEvent->getData();

        if ($emailOrigin instanceof UserEmailOrigin) {
            $this->updateForm($form, $emailOrigin);
        }
    }

    protected function updateForm(FormInterface $form, UserEmailOrigin $emailOrigin)
    {
        $token = $emailOrigin->getAccessToken();
        $accountType = $emailOrigin->getAccountType();
        $isDisabled = $this->isDisabledAvailable($accountType);
        if (!empty($token) || $isDisabled) {
            if (!$form->has('checkFolder') && $emailOrigin->isSyncEnabled()) {
                $form->add('checkFolder', ButtonType::class, [
                    'label' => $this->translator->trans('oro.email.retrieve_folders.label'),
                    'attr' => ['class' => 'btn btn-primary']
                ]);
            }
            if (!$form->has('folders')) {
                $form->add('folders', EmailFolderTreeType::class, [
                    'label' => $this->translator->trans('oro.email.folders.label'),
                    'attr' => ['class' => 'folder-tree'],
                    'tooltip' => $this->translator->trans('oro.email.folders.tooltip'),
                ]);
            }

            if ($form->has('check')) {
                if ($emailOrigin->isSyncEnabled()) {
                    $form->remove('check');
                } elseif (null !== $emailOrigin->getId()) {
                    $form->remove('check');
                    $form->add('check', ButtonType::class, [
                        'label' => $this->translator->trans('oro.imap.configuration.reconnect'),
                        'attr' => ['class' => 'btn btn-primary']
                    ]);
                }
            }
        }
    }

    public function disableFoldersButton(FormEvent $formEvent): void
    {
        $form = $formEvent->getForm();
        $emailOrigin = $formEvent->getData();

        if ($emailOrigin instanceof UserEmailOrigin) {
            $this->doDisableFoldersButton($form, $emailOrigin);
        }
    }

    private function doDisableFoldersButton(FormInterface $form, UserEmailOrigin $emailOrigin): void
    {
        $isDisabled = $this->isDisabledAvailable($emailOrigin->getAccountType());
        if ($isDisabled && $form->has('checkFolder')) {
            if ($form->has('checkFolder')) {
                $form->remove('checkFolder');
            }
            if (!$form->has('checkFolder')) {
                $form->add('checkFolder', ButtonType::class, [
                    'label' => $this->translator->trans('oro.email.retrieve_folders.label'),
                    'attr' => ['class' => 'btn btn-primary'],
                    'disabled' => 1
                ]);
            }
        }
    }

    /**
     * Returns true if given account type is available in the system
     * but was disabled
     */
    private function isDisabledAvailable(string $accountType): bool
    {
        return $this->oauthManagerRegistry->hasManager($accountType)
            && !$this->oauthManagerRegistry->isOauthImapEnabled($accountType);
    }
}
