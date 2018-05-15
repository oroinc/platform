<?php

namespace Oro\Bundle\EmailBundle\Form\Handler;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Event\MailboxSaved;
use Oro\Bundle\EmailBundle\Form\Type\MailboxType;
use Oro\Bundle\EmailBundle\Mailbox\MailboxProcessStorage;
use Oro\Bundle\SoapBundle\Controller\Api\FormAwareInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class MailboxHandler implements FormAwareInterface
{
    const FORM = MailboxType::class;

    /** @var Registry */
    protected $doctrine;

    /** @var FormInterface */
    protected $form;

    /** @var MailboxProcessStorage */
    protected $mailboxProcessStorage;

    /** @var RequestStack */
    protected $requestStack;

    /** @var FormFactoryInterface */
    private $formFactory;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /**
     * @param FormFactoryInterface     $formFactory
     * @param RequestStack             $requestStack
     * @param Registry                 $doctrine
     * @param MailboxProcessStorage    $mailboxProcessStorage
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        RequestStack $requestStack,
        Registry $doctrine,
        MailboxProcessStorage $mailboxProcessStorage,
        EventDispatcherInterface $dispatcher
    ) {
        $this->doctrine              = $doctrine;
        $this->formFactory           = $formFactory;
        $this->form                  = $this->formFactory->create(self::FORM);
        $this->requestStack          = $requestStack;
        $this->mailboxProcessStorage = $mailboxProcessStorage;
        $this->dispatcher            = $dispatcher;
    }

    /**
     * Process form.
     *
     * @param Mailbox $mailbox
     *
     * @return bool True on success.
     */
    public function process(Mailbox $mailbox)
    {
        $this->form->setData($mailbox);

        $request = $this->requestStack->getCurrentRequest();
        if (in_array($request->getMethod(), ['POST', 'PUT'], true)) {
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
    protected function onSuccess()
    {
        /** @var Mailbox $mailbox */
        $mailbox = $this->form->getData();
        $this->getEntityManager()->persist($mailbox);
        $this->getEntityManager()->flush();

        if ($this->dispatcher->hasListeners(MailboxSaved::NAME)) {
            $this->dispatcher->dispatch(MailboxSaved::NAME, new MailboxSaved($mailbox));
        }
    }

    /**
     * Processing of form reload.
     */
    protected function processReload()
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

        $this->form = $this->formFactory->create(self::FORM, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->doctrine->getManager();
    }
}
