<?php

namespace Oro\Bundle\UserBundle\Form\Handler;

use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Mailer\Processor;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class SetPasswordHandler
 *
 * @package Oro\Bundle\UserBundle\Form\Handler
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SetPasswordHandler
{
    use RequestHandlerTrait;

    /** @var LoggerInterface */
    protected $logger;

    /** @var RequestStack */
    protected $requestStack;

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
     * @param RequestStack $requestStack
     * @param TranslatorInterface $translator
     * @param FormInterface $form
     * @param Processor $mailerProcessor
     * @param UserManager $userManager
     * @param ValidatorInterface $validator
     */
    public function __construct(
        LoggerInterface $logger,
        RequestStack    $requestStack,
        TranslatorInterface $translator,
        FormInterface   $form,
        Processor       $mailerProcessor,
        UserManager     $userManager,
        ValidatorInterface $validator
    ) {
        $this->logger          = $logger;
        $this->requestStack    = $requestStack;
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
        $request = $this->requestStack->getCurrentRequest();
        if (in_array($request->getMethod(), ['POST', 'PUT'], true)) {
            $this->submitPostPutRequest($this->form, $request);
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
