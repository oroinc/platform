<?php

namespace Oro\Bundle\SecurityBundle\Http\Firewall;

use Doctrine\ORM\EntityManager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Csrf\CsrfProvider\CsrfProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Firewall\UsernamePasswordFormAuthenticationListener;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;

use Psr\Log\LoggerInterface;

use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class UsernamePasswordOrganizationFormAuthenticationListener extends UsernamePasswordFormAuthenticationListener
{
    /**
     * @var CsrfProviderInterface
     */
    protected $csrfProvider;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        SecurityContextInterface $securityContext,
        AuthenticationManagerInterface $authenticationManager,
        SessionAuthenticationStrategyInterface $sessionStrategy,
        HttpUtils $httpUtils,
        $providerKey,
        AuthenticationSuccessHandlerInterface $successHandler,
        AuthenticationFailureHandlerInterface $failureHandler,
        array $options = array(),
        LoggerInterface $logger = null,
        EventDispatcherInterface $dispatcher = null,
        CsrfProviderInterface $csrfProvider = null
    ) {
        parent::__construct(
            $securityContext,
            $authenticationManager,
            $sessionStrategy,
            $httpUtils,
            $providerKey,
            $successHandler,
            $failureHandler,
            $options,
            $logger,
            $dispatcher,
            $csrfProvider
        );

        $this->csrfProvider = $csrfProvider;
    }

    /**
     * @param EntityManager $entityManager
     */
    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function attemptAuthentication(Request $request)
    {
        $organization = $this->getOrganization(
            $request->get(
                $this->options['organization_parameter'],
                null,
                true
            )
        );

        if (null !== $this->csrfProvider) {
            $csrfToken = $request->get($this->options['csrf_parameter'], null, true);

            if (false === $this->csrfProvider->isCsrfTokenValid($this->options['intention'], $csrfToken)) {
                throw new InvalidCsrfTokenException('Invalid CSRF token.');
            }
        }

        if ($this->options['post_only']) {
            $username = trim($request->request->get($this->options['username_parameter'], null, true));
            $password = $request->request->get($this->options['password_parameter'], null, true);
        } else {
            $username = trim($request->get($this->options['username_parameter'], null, true));
            $password = $request->get($this->options['password_parameter'], null, true);
        }

        $request->getSession()->set(SecurityContextInterface::LAST_USERNAME, $username);

        return $this->authenticationManager->authenticate(
            new UsernamePasswordOrganizationToken(
                $username,
                $password,
                $this->providerKey,
                $organization,
                array()
            )
        );
    }

    /**
     * @param int $id
     * @return Organization
     * @throws BadCredentialsException
     */
    protected function getOrganization($id)
    {
        $organization = $this->entityManager->getRepository('OroOrganizationBundle:Organization')->find($id);
        if (!$organization) {
            throw new BadCredentialsException('Wrong Organization');
        }

        return $organization;
    }
}
