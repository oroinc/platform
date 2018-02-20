<?php

namespace Oro\Bundle\SyncBundle\Authentication\Ticket;

use Oro\Bundle\SyncBundle\Authentication\Ticket\TicketDigestStorage\TicketDigestStorageInterface;
use Oro\Bundle\UserBundle\Security\UserProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Provider for getting and validating Sync authentication tickets.
 */
class TicketProvider
{
    /** @var MessageDigestPasswordEncoder */
    private $encoder;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var UserProvider */
    private $userProvider;

    /** @var string */
    private $salt;

    /** @var TicketDigestStorageInterface */
    private $ticketDigestStorage;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param TicketDigestStorageInterface $ticketDigestStorage
     * @param UserProvider $userProvider
     * @param LoggerInterface $logger
     * @param string $salt
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        TicketDigestStorageInterface $ticketDigestStorage,
        UserProvider $userProvider,
        LoggerInterface $logger,
        $salt
    ) {
        $this->encoder = new MessageDigestPasswordEncoder();
        $this->tokenStorage = $tokenStorage;
        $this->ticketDigestStorage = $ticketDigestStorage;
        $this->userProvider = $userProvider;
        $this->logger = $logger;
        $this->salt = $salt;
    }

    /**
     * @param bool $anonymousTicket
     *
     * @return string
     */
    public function generateTicket($anonymousTicket = false)
    {
        $created = date('c');
        $nonce = base64_encode(substr(md5(uniqid(gethostname() . '_', true)), 0, 16));

        if ($anonymousTicket) {
            $userName = '';
            $password = $this->salt;
        } else {
            $token = $this->tokenStorage->getToken();
            /** @var UserInterface $user */
            $user = $token->getUser();
            $password = $user->getPassword();
            $userName = $user->getUsername();
        }

        $passwordDigest = $this->generateDigest($nonce, $created, $password);

        $ticketId = $this->ticketDigestStorage->saveTicketDigest($passwordDigest);

        $this->logger->debug(
            'Generate Sync ticket',
            ['ticketId' => $ticketId, 'userName' => $userName]
        );

        return sprintf('%s;%s;%s;%s', $ticketId, $userName, $nonce, $created);
    }

    /**
     * @param $ticket
     *
     * @return bool
     */
    public function isTicketValid($ticket)
    {
        list($ticketId, $userName, $nonce, $created) = explode(';', $ticket);

        // check if the ticket from the future
        if (strtotime($created) > strtotime(date('c'))) {
            $this->logger->warning(
                'Ticket is not valid, because it have created date from the future',
                ['ticketId' => $ticketId, 'userName' => $userName, 'created' => $created]
            );
            return false;
        }

        // take the saved digest. if it does not exists - ticket is not valid
        $passwordDigest = $this->ticketDigestStorage->getTicketDigest($ticketId);
        if (!$passwordDigest) {
            $this->logger->warning(
                'Ticket is not valid, because we have no saved Digest for it',
                ['ticketId' => $ticketId, 'userName' => $userName, 'created' => $created]
            );
            return false;
        }

        $password = $this->salt;
        if ($userName) {
            try {
                $user = $this->userProvider->loadUserByUsername($userName);
            } catch (UsernameNotFoundException $exception) {
                $this->logger->warning(
                    'Ticket is not valid, because there is no user with username "{userName}"',
                    ['ticketId' => $ticketId, 'userName' => $userName, 'created' => $created]
                );

                return false;
            }

            $password = $user->getPassword();
        }

        // compare digests
        $expected = $this->generateDigest($nonce, $created, $password);
        $isValid = $passwordDigest === $expected;

        if ($isValid) {
            $this->logger->debug(
                'Ticket is valid',
                ['ticketId' => $ticketId, 'userName' => $userName, 'created' => $created]
            );
        } else {
            $this->logger->warning(
                'Ticket is not valid, because Digest is not valid',
                ['ticketId' => $ticketId, 'userName' => $userName, 'created' => $created]
            );
        }

        return $isValid;
    }

    /**
     * @param string $nonce
     * @param string $created
     * @param string $password
     *
     * @return string
     */
    private function generateDigest($nonce, $created, $password)
    {
        return $this->encoder->encodePassword(
            sprintf(
                '%s%s%s',
                base64_decode($nonce),
                $created,
                $password
            ),
            $this->salt
        );
    }
}
