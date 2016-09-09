<?php

namespace Oro\Bundle\ImapBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;

class GmailOAuthSubscriber implements EventSubscriberInterface
{
    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SUBMIT   => 'setToken',
            FormEvents::PRE_SET_DATA   => 'extendForm'
        ];
    }

    /**
     * @param FormEvent $formEvent
     */
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

    /**
     * @param FormEvent $formEvent
     */
    public function extendForm(FormEvent $formEvent)
    {
        $form = $formEvent->getForm();
        $emailOrigin = $formEvent->getData();

        if ($emailOrigin instanceof UserEmailOrigin) {
            $this->updateForm($form, $emailOrigin);
        }
    }

    /**
     * @param FormInterface $form
     * @param UserEmailOrigin $emailOrigin
     */
    protected function updateForm(FormInterface $form, UserEmailOrigin $emailOrigin)
    {
        //for empty() function must be only variable for compatibility with PHP 5.4
        $token = $emailOrigin->getAccessToken();
        if (!empty($token)) {
            $form->add('checkFolder', 'button', [
                'label' => $this->translator->trans('oro.email.retrieve_folders.label'),
                'attr' => ['class' => 'btn btn-primary']
            ])
                ->add('folders', 'oro_email_email_folder_tree', [
                    'label' => $this->translator->trans('oro.email.folders.label'),
                    'attr' => ['class' => 'folder-tree'],
                    'tooltip' => $this->translator->trans('oro.email.folders.tooltip'),
                ]);

            $form->remove('check');
        }
    }
}
