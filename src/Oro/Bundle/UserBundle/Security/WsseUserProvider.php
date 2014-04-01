<?php

namespace Oro\Bundle\UserBundle\Security;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

use Doctrine\Common\Cache\Cache;

use Escape\WSSEAuthenticationBundle\Security\Core\Authentication\Provider\Provider;

class WsseUserProvider extends Provider
{
    /**
     * Need to override parent's "private" declaration
     *
     * @var UserProviderInterface
     */
    protected $userProvider;

    /**
     * Constructor.
     *
     * @param UserProviderInterface    $userProvider    An UserProviderInterface instance
     * @param PasswordEncoderInterface $encoder         A PasswordEncoderInterface instance
     * @param Cache                    $nonceCache      Cache instance
     * @param int                      $lifetime        The lifetime, in seconds
     */
    public function __construct(
        UserProviderInterface $userProvider,
        PasswordEncoderInterface $encoder,
        Cache $nonceCache,
        $lifetime = 300
    ) {
        parent::__construct($userProvider, $encoder, $nonceCache, $lifetime);

        $this->userProvider = $userProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSecret(UserInterface $user)
    {
        return $user->getApi()->getApiKey();
    }
}
