<?php

namespace Oro\Bundle\UserBundle\Form\Handler;

use Psr\Log\LoggerInterface;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\Translator;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Mailer\Processor;

class SetPasswordHandler
{
    /** @var  LoggerInterface */
    protected $logger;

    /** @var Request */
    protected $request;

    /** @var Translator */
    protected $translator;

    /** @var FormInterface */
    protected $form;

    /** @var Processor */
    protected $mailerProcessor;

    /**
     * @param LoggerInterface     $logger
     * @param Request             $request
     * @param Translator          $translator
     * @param FormInterface       $form
     * @param Processor           $mailerProcessor
     */
    public function __construct(
        LoggerInterface  $logger,
        Request          $request,
        Translator       $translator,
        FormInterface    $form,
        Processor        $mailerProcessor
    ) {
        $this->logger          = $logger;
        $this->request         = $request;
        $this->translator      = $translator;
        $this->form            = $form;
        $this->mailerProcessor = $mailerProcessor;
    }

    /**
     * Process form
     *
     * @param  User $entity
     *
     * @return bool  True on successful processing, false otherwise
     */
    public function process(User $entity)
    {
        if (in_array($this->request->getMethod(), ['POST', 'PUT'])) {
            $this->form->submit($this->request);
            if ($this->form->isValid()) {
                try {
                    $entity->setPlainPassword($this->form->get('password')->getData());
                    $this->mailerProcessor->sendEmail($entity);

                    return true;
                } catch (\Exception $ex) {
                    $this->logger->error('Email sending failed.', ['exception' => $ex]);
                    $this->form->addError(
                        new FormError($this->translator->trans('oro.email.handler.unable_to_send_email'))
                    );
                }
            }
        }

        return false;
    }
}
