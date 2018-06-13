<?php

namespace Oro\Bundle\SyncBundle\Security;

use Oro\Bundle\SyncBundle\Authentication\Ticket\TicketDigestGenerator\TicketDigestGeneratorInterface;
use Oro\Bundle\SyncBundle\Security\Token\AnonymousTicketToken;
use Oro\Bundle\SyncBundle\Security\Token\TicketToken;
use Oro\Bundle\UserBundle\Security\UserProvider;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken as Token;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class TicketAuthenticationProvider implements AuthenticationProviderInterface
{
    /**
     * @var TicketDigestGeneratorInterface
     */
    private $ticketDigestGenerator;

    /**
     * @var UserProvider
     */
    private $userProvider;

    /**
     * @var string
     */
    private $providerKey;

    /**
     * @var string
     */
    private $secret;

    /**
     * @var int
     */
    private $ticketTtl;

    /**
     * @param TicketDigestGeneratorInterface $ticketDigestGenerator
     * @param UserProvider $userProvider
     * @param string $providerKey
     * @param string $secret
     * @param string|int $ticketTtl
     */
    public function __construct(
        TicketDigestGeneratorInterface $ticketDigestGenerator,
        UserProvider $userProvider,
        string $providerKey,
        string $secret,
        $ticketTtl
    ) {
        $this->userProvider = $userProvider;
        $this->ticketDigestGenerator = $ticketDigestGenerator;
        $this->providerKey = $providerKey;
        $this->secret = $secret;
        $this->ticketTtl = (int)$ticketTtl;
    }

    /**
     * {@inheritDoc}
     *
     * @throws BadCredentialsException
     */
    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            throw new \InvalidArgumentException('Token is not supported');
        }

        $this->validateTokenCreatedDate($token);

        $ticketDigest = $token->getCredentials();
        $nonce = $token->getAttribute('nonce');
        $created = $token->getAttribute('created');

        $password = $this->secret;
        $user = $this->fetchUser($token);
        if ($user !== null) {
            $password = $user->getPassword();
        }

        $expectedDigest = $this->ticketDigestGenerator->generateDigest($nonce, $created, $password);
        if ($ticketDigest === $expectedDigest) {
            if ($user !== null) {
                $token = new TicketToken(
                    $user,
                    $ticketDigest,
                    $this->providerKey,
                    $user->getRoles()
                );
            } else {
                $token = new AnonymousTicketToken($ticketDigest, static::USERNAME_NONE_PROVIDED);
            }

            return $token;
        }

        throw new BadCredentialsException(sprintf(
            'Ticket "%s" for "%s" is not valid - invalid credentials',
            $token->getCredentials(),
            $token->getUsername()
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof Token &&
            $token->hasAttribute('nonce') &&
            $token->hasAttribute('created') &&
            $this->providerKey === $token->getProviderKey();
    }

    /**
     * @param TokenInterface $token
     *
     * @throws BadCredentialsException
     */
    private function validateTokenCreatedDate(TokenInterface $token): void
    {
        $created = $token->getAttribute('created');

        $createdTime = strtotime($created);
        $now = strtotime(date('c'));
        if ($createdTime > $now) {
            throw new BadCredentialsException(sprintf(
                'Ticket "%s" for "%s" is not valid, because token creation date "%s" is in future',
                $token->getCredentials(),
                $token->getUsername(),
                $created
            ));
        }

        if ($now - $createdTime > $this->ticketTtl) {
            throw new BadCredentialsException(sprintf(
                'Ticket "%s" for "%s" is expired',
                $token->getCredentials(),
                $token->getUsername()
            ));
        }
    }

    /**
     * @param TokenInterface $token
     *
     * @return null|UserInterface
     */
    private function fetchUser(TokenInterface $token): ?UserInterface
    {
        $username = $token->getUsername();
        $user = null;

        if ($username) {
            try {
                $user = $this->userProvider->loadUserByUsername($username);
            } catch (UsernameNotFoundException $exception) {
                throw new BadCredentialsException(sprintf(
                    'Ticket "%s" for "%s" is not valid - invalid credentials',
                    $token->getCredentials(),
                    $token->getUsername()
                ));
            }
        }

        return $user;
    }
}
