<?php

namespace Oro\Bundle\UserBundle\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

use Doctrine\ORM\PersistentCollection;

use Escape\WSSEAuthenticationBundle\Security\Core\Authentication\Provider\Provider;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserApi;

/**
 * Class WsseAuthProvider
 * This override needed to use random generated API key for WSSE auth instead regular user password.
 * In order to prevent usage of user password in third party software.
 * In case if not ORO user is used this provider fallback to native behavior.
 *
 * @package Oro\Bundle\UserBundle\Security
 */
class WsseAuthProvider extends Provider
{
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
        /** @var User $user */
        $user = $this->getUserProvider()->loadUserByUsername($token->getUsername());
        if ($user) {
            $secret = $this->getSecret($user);
            if ($secret instanceof PersistentCollection) {
                $correctUserAPI = $this->getValidUserApi($token, $secret, $user);
                if ($correctUserAPI) {
                    $authenticatedToken = new WsseToken($user->getRoles());
                    $authenticatedToken->setUser($user);
                    $authenticatedToken->setOrganizationContext($correctUserAPI->getOrganization());
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
     * @param PersistentCollection $secrets
     * @param User                 $user
     *
     * @return bool|UserApi
     */
    protected function getValidUserApi(TokenInterface $token, PersistentCollection $secrets, User $user)
    {
        $currentIteration = 0;
        $nonce            = $token->getAttribute('nonce');
        $secretsCount     = $secrets->count();

        foreach ($secrets as $userApi) {
            $currentIteration++;
            $isSecretValid = $this->validateDigest(
                $token->getAttribute('digest'),
                $nonce,
                $token->getAttribute('created'),
                $userApi->getApiKey(),
                $this->getSalt($user)
            );

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
}
