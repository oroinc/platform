<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Authenticator;

use Oro\Bundle\FormBundle\Captcha\CaptchaSettingsProviderInterface;
use Oro\Bundle\SecurityBundle\Authentication\Guesser\OrganizationGuesserInterface;
use Oro\Bundle\SecurityBundle\Authentication\Passport\Badge\CaptchaBadge;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationTokenFactoryInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Exception\BadCredentialsException as BadUserCredentialsException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\HttpUtils;

/**
 * The authentication provider to retrieve the user and the organization for a UsernamePasswordOrganizationToken.
 */
class UsernamePasswordOrganizationAuthenticator extends FormLoginAuthenticator
{
    private UsernamePasswordOrganizationTokenFactoryInterface $tokenFactory;
    private OrganizationGuesserInterface $organizationGuesser;
    private ?CaptchaSettingsProviderInterface $captchaSettingsProvider = null;
    private array $firewallToLoginForm = [];

    public function __construct(
        HttpUtils $httpUtils,
        UserProviderInterface $userProvider,
        AuthenticationSuccessHandlerInterface $successHandler,
        AuthenticationFailureHandlerInterface $failureHandler,
        array $options,
        private string $firewallName,
        private array $errorMessageOptions
    ) {
        parent::__construct($httpUtils, $userProvider, $successHandler, $failureHandler, $options);
    }

    public function setTokenFactory(UsernamePasswordOrganizationTokenFactoryInterface $tokenFactory)
    {
        $this->tokenFactory = $tokenFactory;
    }

    public function setOrganizationGuesser(OrganizationGuesserInterface $organizationGuesser)
    {
        $this->organizationGuesser = $organizationGuesser;
    }

    public function setCaptchaSettingsProvider(CaptchaSettingsProviderInterface $captchaSettingsProvider)
    {
        $this->captchaSettingsProvider = $captchaSettingsProvider;
    }

    public function mapFirewallToLoginForm(string $firewallName, string $formName)
    {
        $this->firewallToLoginForm[$firewallName] = $formName;
    }

    #[\Override]
    public function authenticate(Request $request): Passport
    {
        $passport = parent::authenticate($request);

        $request->attributes->set(
            Security::LAST_USERNAME,
            $passport->getBadge(UserBadge::class)->getUserIdentifier()
        );
        $request->attributes->set('user', $this->getUser($passport));

        if ($this->isCaptchaProtected()) {
            $passport->addBadge(new CaptchaBadge($request->request->get('captcha')));
        }

        return $passport;
    }

    #[\Override]
    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        if (null === $this->tokenFactory) {
            throw new AuthenticationException(
                'Token Factory is not set in UsernamePasswordOrganizationAuthenticationProvider.'
            );
        }
        if (null === $this->organizationGuesser) {
            throw new AuthenticationException(
                'Organization Guesser is not set in UsernamePasswordOrganizationAuthenticationProvider.'
            );
        }
        /** @var User $user */
        $user = $passport->getUser();
        $organization = $this->organizationGuesser->guess($user);

        return $this->tokenFactory->create(
            $user,
            $firewallName,
            $organization,
            $user->getRoles()
        );
    }

    #[\Override]
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($exception instanceof BadCredentialsException
            && !$exception instanceof BadUserCredentialsException
            && isset($this->errorMessageOptions[$this->firewallName])
        ) {
            $previousException = $exception;
            $exception = new BadUserCredentialsException(
                $previousException->getMessage(),
                $previousException->getCode(),
                $previousException->getPrevious()
            );
            $exception->setMessageKey($this->errorMessageOptions[$this->firewallName]);
        }

        return parent::onAuthenticationFailure($request, $exception);
    }

    public function getFirewallName(): string
    {
        return $this->firewallName;
    }

    private function getUser(Passport $passport): ?object
    {
        try {
            return $passport->getUser();
        } catch (\Exception $e) {
            return null;
        }
    }

    private function isCaptchaProtected(): bool
    {
        $formName = $this->firewallToLoginForm[$this->firewallName] ?? null;
        if (!$formName) {
            return false;
        }

        return $this->captchaSettingsProvider?->isProtectionAvailable()
            && $this->captchaSettingsProvider?->isFormProtected($formName);
    }
}
