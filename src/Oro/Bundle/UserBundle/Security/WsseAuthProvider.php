<?php

namespace Oro\Bundle\UserBundle\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

use Doctrine\Common\Collections\Collection;

use Escape\WSSEAuthenticationBundle\Security\Core\Authentication\Provider\Provider;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserApi;

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
     * @var string The security firewall name whose urls should process by this provider
     */
    private $firewallName;

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
                if ($user instanceof User) {
                    $validUserApi = $this->getValidUserApi($token, $secret, $user);
                } else {
                    $validUserApi = $this->getValidUserApiNew($token, $secret, $user);
                }
                if ($validUserApi) {
                    $authenticatedToken = $this->tokenFactory->create($user->getRoles());
                    $authenticatedToken->setUser($user);
                    $authenticatedToken->setOrganizationContext($validUserApi->getOrganization());
                    $authenticatedToken->setAuthenticated(true);

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
     * @param TokenInterface       $token
     * @param Collection $secrets
     * @param User                 $user
     *
     * @return bool|UserApi
     */
    protected function getValidUserApi(TokenInterface $token, Collection $secrets, User $user)
    {
        return $this->getValidUserApiNew($token, $secrets, $user);
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
    protected function getValidUserApiNew(TokenInterface $token, Collection $secrets, UserInterface $user)
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
                    $token->getAttribute('digest'),
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
