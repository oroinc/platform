<?php

namespace Oro\Bundle\EmailBundle\Form\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Event\MailboxSaved;
use Oro\Bundle\EmailBundle\Form\Type\MailboxType;
use Oro\Bundle\EmailBundle\Mailbox\MailboxProcessStorage;
use Oro\Bundle\SoapBundle\Controller\Api\FormAwareInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles a mailbox form.
 */
class MailboxHandler implements FormAwareInterface
{
    protected FormInterface $form;

    public function __construct(
        protected FormFactoryInterface $formFactory,
        protected RequestStack $requestStack,
        protected ManagerRegistry $doctrine,
        protected MailboxProcessStorage $mailboxProcessStorage,
        protected EventDispatcherInterface $dispatcher
    ) {
        $this->form = $this->formFactory->create(MailboxType::class);
    }

    public function process(Mailbox $mailbox): bool
    {
        $this->form->setData($mailbox);

        $request = $this->requestStack->getCurrentRequest();
        if (\in_array($request->getMethod(), ['POST', 'PUT'], true)) {
            // If this request is marked as reload, process as reload.
            if ($request->get(MailboxType::RELOAD_MARKER, false)) {
                $this->processReload();
            } else {
                $this->form->handleRequest($request);
                if ($this->form->isSubmitted() && $this->form->isValid()) {
                    $this->onSuccess();

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Form validated and can be processed.
     */
    protected function onSuccess(): void
    {
        /** @var Mailbox $mailbox */
        $mailbox = $this->form->getData();
        $em = $this->doctrine->getManager();
        $em->persist($mailbox);
        $em->flush();

        if ($this->dispatcher->hasListeners(MailboxSaved::NAME)) {
            $this->dispatcher->dispatch(new MailboxSaved($mailbox), MailboxSaved::NAME);
        }
    }

    /**
     * Processing of form reload.
     */
    protected function processReload(): void
    {
        $this->form->handleRequest($this->requestStack->getCurrentRequest());

        $type = $this->form->get('processType')->getViewData();
        /** @var Mailbox $data */
        $data = $this->form->getData();

        if (!empty($type)) {
            $processorEntity = $this->mailboxProcessStorage->getNewSettingsEntity($type);
            $data->setProcessSettings($processorEntity);
        } else {
            $data->setProcessSettings(null);
        }

        $this->form = $this->formFactory->create(MailboxType::class, $data);
    }

    #[\Override]
    public function getForm()
    {
        return $this->form;
    }
}
