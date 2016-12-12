<?php

namespace Oro\Bundle\UserBundle\Form\Handler;

use Psr\Log\LoggerInterface;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Mailer\Processor;

/**
 * Class SetPasswordHandler
 *
 * @package Oro\Bundle\UserBundle\Form\Handler
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SetPasswordHandler
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var Request */
    protected $request;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var FormInterface */
    protected $form;

    /** @var Processor */
    protected $mailerProcessor;

    /** @var UserManager */
    protected $userManager;

    /** @var ValidatorInterface */
    protected $validator;

    /**
     * @param LoggerInterface $logger
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param FormInterface $form
     * @param Processor $mailerProcessor
     * @param UserManager $userManager
     * @param ValidatorInterface $validator
     */
    public function __construct(
        LoggerInterface $logger,
        Request         $request,
        TranslatorInterface $translator,
        FormInterface   $form,
        Processor       $mailerProcessor,
        UserManager     $userManager,
        ValidatorInterface $validator
    ) {
        $this->logger          = $logger;
        $this->request         = $request;
        $this->translator      = $translator;
        $this->form            = $form;
        $this->mailerProcessor = $mailerProcessor;
        $this->userManager     = $userManager;
        $this->validator       = $validator;
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
                $entity->setPlainPassword($this->form->get('password')->getData());
                $entity->setPasswordChangedAt(new \DateTime());

                $errors = $this->validator->validate($entity, null, ['security']);
                if (count($errors) > 0) {
                    foreach ($errors as $error) {
                        $this->form->addError(new FormError($error->getMessage()));
                    }

                    return false;
                }

                try {
                    $this->mailerProcessor->sendChangePasswordEmail($entity);
                } catch (\Exception $e) {
                    $this->form->addError(
                        new FormError($this->translator->trans('oro.email.handler.unable_to_send_email'))
                    );
                    $this->logger->error('Email sending failed.', ['exception' => $e]);
                    return false;
                }

                $this->userManager->updateUser($entity);
                return true;
            }
        }

        return false;
    }
}
