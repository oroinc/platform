<?php

namespace Oro\Bundle\UserBundle\Security;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Collections\Collection;
use Escape\WSSEAuthenticationBundle\Security\Core\Authentication\Provider\Provider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * This override needed to use random generated API key for WSSE auth instead regular user password.
 * In order to prevent usage of user password in third party software.
 * In case if not ORO user is used this provider fallback to native behavior.
 */
class WsseAuthProvider extends Provider
{
    /**
     * @var WsseTokenFactoryInterface
     */
    protected $tokenFactory;

    /**
     * @var string
     */
    protected $providerKey;

    /**
     * @var string The security firewall name whose urls should process by this provider
     */
    private $firewallName;

    /**
     * @param UserCheckerInterface     $userChecker  A UserChecketerInterface instance
     * @param UserProviderInterface    $userProvider An UserProviderInterface instance
     * @param string                   $providerKey  The provider key
     * @param PasswordEncoderInterface $encoder      A PasswordEncoderInterface instance
     * @param Cache                    $nonceCache   The nonce cache
     * @param int                      $lifetime     The lifetime
     * @param string                   $dateFormat   The date format
     */
    public function __construct(
        UserCheckerInterface $userChecker,
        UserProviderInterface $userProvider,
        $providerKey,
        PasswordEncoderInterface $encoder,
        Cache $nonceCache,
        $lifetime = 300,
        $dateFormat = '/^([\+-]?\d{4}(?!\d{2}\b))((-?)((0[1-9]|1[0-2])(\3([12]\d|0[1-9]|3[01]))?|W([0-4]\d|5[0-2])'.
        '(-?[1-7])?|(00[1-9]|0[1-9]\d|[12]\d{2}|3([0-5]\d|6[1-6])))([T\s]((([01]\d|2[0-3])((:?)[0-5]\d)?|24\:?00)'.
        '([\.,]\d+(?!:))?)?(\17[0-5]\d([\.,]\d+)?)?([zZ]|([\+-])([01]\d|2[0-3]):?([0-5]\d)?)?)?)?$/'
    ) {
        $this->providerKey = $providerKey;

        parent::__construct($userChecker, $userProvider, $providerKey, $encoder, $nonceCache, $lifetime, $dateFormat);
    }

    /**
     * Sets the security firewall name whose urls should process by this provider.
     *
     * @param string $firewallName
     */
    public function setFirewallName($firewallName)
    {
        $this->firewallName = $firewallName;
    }

    /**
     * @param WsseTokenFactoryInterface $tokenFactory
     */
    public function setTokenFactory(WsseTokenFactoryInterface $tokenFactory)
    {
        $this->tokenFactory = $tokenFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSecret(UserInterface $user)
    {
        if ($user instanceof AdvancedApiUserInterface) {
            return $user->getApiKeys();
        }

        return parent::getSecret($user);
    }

    /**
     * {@inheritdoc}
     */
    protected function getSalt(UserInterface $user)
    {
        if ($user instanceof AdvancedApiUserInterface) {
            return '';
        }

        return parent::getSalt($user);
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(TokenInterface $token)
    {
        if (null === $this->tokenFactory) {
            throw new AuthenticationException('Token Factory is not set in WsseAuthProvider.');
        }

        $user = $this->getUserProvider()->loadUserByUsername($token->getUsername());
        if ($user) {
            if ($user instanceof AdvancedUserInterface && !$user->isEnabled()) {
                throw new BadCredentialsException('User is not active.');
            }
            $secret = $this->getSecret($user);
            if ($secret instanceof Collection) {
                $validUserApi = $this->getValidUserApi($token, $secret, $user);
                if ($validUserApi) {
                    $authenticatedToken = $this->tokenFactory->create(
                        $user,
                        $token->getCredentials(),
                        $this->providerKey,
                        $user->getRoles()
                    );
                    $authenticatedToken->setOrganizationContext($validUserApi->getOrganization());

                    return $authenticatedToken;
                }
            } else {
                return parent::authenticate($token);
            }
        }

        throw new AuthenticationException('WSSE authentication failed.');
    }

    /**
     * Get valid UserApi for given token
     *
     * @param TokenInterface $token
     * @param Collection     $secrets
     * @param UserInterface  $user
     *
     * @return bool|UserApiKeyInterface
     */
    protected function getValidUserApi(TokenInterface $token, Collection $secrets, UserInterface $user)
    {
        $currentIteration = 0;
        $nonce            = $token->getAttribute('nonce');
        $secretsCount     = $secrets->count();

        /** @var UserApiKeyInterface $userApi */
        foreach ($secrets as $userApi) {
            $currentIteration++;
            $secret = $userApi->getApiKey();
            $isSecretValid = false;
            if ($secret) {
                $isSecretValid = $this->validateDigest(
                    $token->getCredentials(),
                    $nonce,
                    $token->getAttribute('created'),
                    $secret,
                    $this->getSalt($user)
                );
            }

            if ($isSecretValid) {
                if (!$userApi->getOrganization()->isEnabled()) {
                    throw new BadCredentialsException('Organization is not active.');
                }
                if (!$userApi->isEnabled()) {
                    throw new BadCredentialsException('Wrong API key.');
                }
            }

            // delete nonce from cache because user have another api keys
            if (!$isSecretValid && $secretsCount !== $currentIteration) {
                $this->getNonceCache()->delete($nonce);
            }

            if ($isSecretValid) {
                return $userApi;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(TokenInterface $token)
    {
        return parent::supports($token)
            && $token->hasAttribute('firewallName')
            && $token->getAttribute('firewallName') === $this->firewallName;
    }
}
