<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Oro\Bundle\UserBundle\Exception\BadCredentialsException as BadUserCredentialsException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * Modifies login failure message
 */
class AuthenticationFailureListener
{
    /** @var string */
    private $providerKey;

    /** @var string */
    private $messageKey;

    /**
     * @param string $providerKey
     * @param string $messageKey
     */
    public function __construct(string $providerKey, string $messageKey)
    {
        $this->providerKey = $providerKey;
        $this->messageKey = $messageKey;
    }

    /**
     * @param AuthenticationFailureEvent $event
     */
    public function onAuthenticationFailure(AuthenticationFailureEvent $event)
    {
        if ($this->isApplicable($event->getAuthenticationException(), $event->getAuthenticationToken())) {
            throw $this->createBadCredentialsException($event->getAuthenticationException());
        }
    }

    /**
     * @param AuthenticationException $exception
     * @param TokenInterface $token
     * @return bool
     */
    protected function isApplicable(AuthenticationException $exception, TokenInterface $token)
    {
        return $exception instanceof BadCredentialsException &&
            strpos($exception->getMessageKey(), 'Invalid credentials.') === 0 &&
            $token instanceof UsernamePasswordToken &&
            $token->getProviderKey() === $this->providerKey;
    }

    /**
     * @param AuthenticationException $previousException
     * @return BadUserCredentialsException
     */
    protected function createBadCredentialsException(AuthenticationException $previousException)
    {
        $exception = new BadUserCredentialsException(
            $previousException->getMessage(),
            $previousException->getCode(),
            $previousException->getPrevious()
        );
        $exception->setMessageKey($this->messageKey);

        return $exception;
    }
}
