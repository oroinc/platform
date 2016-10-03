<?php

namespace Oro\Bundle\UserBundle\Security;

use Doctrine\ORM\EntityManager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorInterface;

use Oro\Bundle\SecurityBundle\Authentication\Guesser\UserOrganizationGuesser;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationTokenFactoryInterface;
use Oro\Bundle\UserBundle\Entity\Impersonation;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Event\ImpersonationSuccessEvent;

class ImpersonationAuthenticator implements GuardAuthenticatorInterface
{
    const TOKEN_PARAMETER = '_impersonation_token';
    const NOTIFY_PARAMETER = '_impersonation_notify';

    /** @var EntityManager */
    protected $em;

    /** @var UsernamePasswordOrganizationTokenFactoryInterface */
    protected $tokenFactory;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var UrlGeneratorInterface */
    protected $router;

    /**
     * @param EntityManager $em
     * @param UsernamePasswordOrganizationTokenFactoryInterface $tokenFactory
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        EntityManager $em,
        UsernamePasswordOrganizationTokenFactoryInterface $tokenFactory,
        EventDispatcherInterface $eventDispatcher,
        UrlGeneratorInterface $router
    ) {
        $this->em = $em;
        $this->tokenFactory = $tokenFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials(Request $request)
    {
        return $request->query->get(static::TOKEN_PARAMETER);
    }

    /**
     * {@inheritdoc}
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        // always returns an Impersonation object or throws an exception
        return $this->getImpersonation($credentials)->getUser();
    }

    /**
     * {@inheritdoc}
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        // checks are already done in getImpersonation
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        if ($request->query->get(static::NOTIFY_PARAMETER)) {
            $event = new ImpersonationSuccessEvent($token->getUser());
            $this->eventDispatcher->dispatch(ImpersonationSuccessEvent::EVENT_NAME, $event);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);

        return new RedirectResponse($this->router->generate('oro_user_security_login'));
    }

    /**
     * {@inheritdoc}
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsRememberMe()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function createAuthenticatedToken(UserInterface $user, $providerKey)
    {
        $guesser = new UserOrganizationGuesser();
        /** @var User $user */
        $organization = $guesser->guessByUser($user);

        if (!$organization) {
            throw new BadCredentialsException("You don't have active organization assigned.");
        }

        return $this->tokenFactory->create($user, null, $providerKey, $organization, $user->getRoles());
    }

    /**
     * Get Impersonation by token and set it's login time
     *
     * @param string $token
     * @throws AuthenticationCredentialsNotFoundException when token is not found
     * @throws CustomUserMessageAuthenticationException when token is already used
     * @throws CustomUserMessageAuthenticationException when token is expired
     * @return Impersonation
     */
    protected function getImpersonation($token)
    {
        $impersonation = $this->em
            ->getRepository('OroUserBundle:Impersonation')
            ->findOneBy(['token' => $token]);

        if (!$impersonation) {
            throw new AuthenticationCredentialsNotFoundException();
        }

        if ($impersonation->getLoginAt()) {
            throw new CustomUserMessageAuthenticationException('Impersonation token is already used.');
        }

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        if ($impersonation->getExpireAt() <= $now) {
            throw new CustomUserMessageAuthenticationException('Impersonation token has expired.');
        }

        $impersonation->setLoginAt($now);

        return $impersonation;
    }
}
