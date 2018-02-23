<?php

namespace Oro\Bundle\SecurityBundle\Http\Firewall;

use Oro\Bundle\OrganizationBundle\Entity\Manager\OrganizationManager;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationTokenFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class OrganizationBasicAuthenticationListener
{
    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var AuthenticationManagerInterface */
    protected $authenticationManager;

    /**  @var string */
    protected $providerKey;

    /** @var AuthenticationEntryPointInterface */
    protected $authenticationEntryPoint;

    /** @var LoggerInterface */
    protected $logger;

    /**  @var bool */
    protected $ignoreFailure;

    /** @var OrganizationManager */
    protected $manager;

    /**
     * @var UsernamePasswordOrganizationTokenFactoryInterface
     */
    protected $tokenFactory;

    /**
     * @param TokenStorageInterface             $tokenStorage
     * @param AuthenticationManagerInterface    $authenticationManager
     * @param string                            $providerKey
     * @param AuthenticationEntryPointInterface $authenticationEntryPoint
     * @param OrganizationManager               $manager
     * @param LoggerInterface                   $logger
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        AuthenticationManagerInterface $authenticationManager,
        $providerKey,
        AuthenticationEntryPointInterface $authenticationEntryPoint,
        OrganizationManager $manager,
        LoggerInterface $logger = null
    ) {
        if (empty($providerKey)) {
            throw new \InvalidArgumentException('$providerKey must not be empty.');
        }

        $this->tokenStorage = $tokenStorage;
        $this->authenticationManager = $authenticationManager;
        $this->providerKey = $providerKey;
        $this->authenticationEntryPoint = $authenticationEntryPoint;
        $this->logger = $logger;
        $this->manager = $manager;
        $this->ignoreFailure = false;
    }

    /**
     * @param UsernamePasswordOrganizationTokenFactoryInterface $tokenFactory
     */
    public function setTokenFactory(UsernamePasswordOrganizationTokenFactoryInterface $tokenFactory)
    {
        $this->tokenFactory = $tokenFactory;
    }

    /**
     * Handles basic authentication.
     *
     * @param GetResponseEvent $event A GetResponseEvent instance
     */
    public function handle(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (false === $username = $request->headers->get('PHP_AUTH_USER', false)) {
            return;
        }

        if (null !== $token = $this->tokenStorage->getToken()) {
            if ($token instanceof OrganizationContextTokenInterface
                && $token->isAuthenticated()
                && $token->getUsername() === $username
            ) {
                return;
            }
        }

        $this->logProcess($username);

        try {
            $organizationId = $request->headers->get('PHP_AUTH_ORGANIZATION');
            if ($organizationId) {
                $authToken = $this->tokenFactory
                    ->create(
                        $username,
                        $request->headers->get('PHP_AUTH_PW'),
                        $this->providerKey,
                        $this->manager->getOrganizationById($organizationId)
                    );
            } else {
                $authToken = new UsernamePasswordToken(
                    $username,
                    $request->headers->get('PHP_AUTH_PW'),
                    $this->providerKey
                );
            }

            $this->tokenStorage->setToken($this->authenticationManager->authenticate($authToken));
        } catch (AuthenticationException $failed) {
            $token = $this->tokenStorage->getToken();
            if ($token instanceof UsernamePasswordToken && $this->providerKey === $token->getProviderKey()) {
                $this->tokenStorage->setToken(null);
            }

            $this->logError($username, $failed->getMessage());

            if ($this->ignoreFailure) {
                return;
            }

            $event->setResponse($this->authenticationEntryPoint->start($request, $failed));
        }
    }

    /**
     * @param string $username
     * @param string $message
     */
    protected function logError($username, $message)
    {
        if (null !== $this->logger) {
            $this->logger->info(
                sprintf('Authentication request failed for user "%s": %s', $username, $message)
            );
        }
    }

    /**
     * @param string $username
     */
    protected function logProcess($username)
    {
        if (null !== $this->logger) {
            $this->logger->info(
                sprintf('Basic Organization Authentication Authorization header found for user "%s"', $username)
            );
        }
    }
}
