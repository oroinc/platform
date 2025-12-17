<?php

namespace Oro\Bundle\UserBundle\Form\Handler;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Event\PasswordChangeEvent;
use Oro\Bundle\UserBundle\Provider\UserLoggingInfoProviderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Handles forgot password request.
 */
class UserPasswordResetHandler
{
    public const SESSION_PASSWORD_RESET_UNAVAILABLE = 'oro_user_password_reset_unavailable';
    public const SESSION_PASSWORD_RESET_UNAVAILABLE_MESSAGE = 'oro_user_password_reset_unavailable_message';

    protected ?EventDispatcherInterface $eventDispatcher = null;

    public function __construct(
        private UserManager $userManager,
        private TranslatorInterface $translator,
        private LoggerInterface $logger,
        private UserLoggingInfoProviderInterface $userLoggingInfoProvider,
        private int $ttl
    ) {
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): self
    {
        $this->eventDispatcher = $eventDispatcher;
        return $this;
    }

    /** @SuppressWarnings(PHPMD.CyclomaticComplexity) */
    public function process(FormInterface $form, Request $request)
    {
        if (!$request->isMethod(Request::METHOD_POST)) {
            return null;
        }

        $form->handleRequest($request);
        if (!$form->isSubmitted() || !$form->isValid()) {
            return null;
        }

        $usernameOrEmail = $form->get('username')->getData();
        $isFrontend = (bool)$form->get('frontend')->getData();

        /** @var User $user */
        $user = $this->userManager->findUserByUsernameOrEmail($usernameOrEmail);
        // For non-existing or disabled user behave like for existing user to prevent username enumeration
        if (!$user?->isEnabled()) {
            return $usernameOrEmail;
        }

        // Dispatch event to allow extensions to prevent password reset
        $event = new PasswordChangeEvent($user);
        $this->eventDispatcher?->dispatch($event, PasswordChangeEvent::BEFORE_PASSWORD_RESET);
        if (!$event->isAllowed()) {
            // Set a session flag to indicate the password reset was denied
            $request->getSession()->set(self::SESSION_PASSWORD_RESET_UNAVAILABLE, true);
            $request->getSession()->set(
                self::SESSION_PASSWORD_RESET_UNAVAILABLE_MESSAGE,
                $event->getNotAllowedMessage()
            );
            $this->logger->notice(
                \sprintf(
                    'Password reset request denied%s.',
                    $event->getNotAllowedLogMessage() ? ' (' . $event->getNotAllowedLogMessage() . ')' : ''
                ),
                $this->userLoggingInfoProvider->getUserLoggingInfo($user)
            );
            return $usernameOrEmail;
        }

        $email = $user->getEmail();
        if ($user->isPasswordRequestNonExpired($this->ttl)
            && !($isFrontend && null === $user->getPasswordRequestedAt())
        ) {
            $this->logger->notice(
                sprintf(
                    'The password for this user has already been requested within the last %d hours.',
                    $this->ttl / 3600 //reset password token ttl in hours
                ),
                $this->userLoggingInfoProvider->getUserLoggingInfo($user)
            );
        } else {
            if (!$this->sendEmail($user, $email, $request)) {
                return null;
            }

            $this->logger->notice(
                'Reset password email has been sent',
                $this->userLoggingInfoProvider->getUserLoggingInfo($user)
            );

            $this->userManager->updateUser($user);
        }

        return $usernameOrEmail;
    }

    private function sendEmail(User $user, string $email, Request $request)
    {
        try {
            $this->userManager->sendResetPasswordEmail($user);

            return true;
        } catch (\Exception $e) {
            $this->logger->error(
                'Unable to sent the reset password email.',
                ['email' => $email, 'exception' => $e]
            );
            $request->getSession()
                ->getFlashBag()
                ->add(
                    'warn',
                    $this->translator->trans('oro.email.handler.unable_to_send_email')
                );

            return false;
        }
    }
}
