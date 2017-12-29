<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Authentication\Ticket;

use Psr\Log\LoggerInterface;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

use Oro\Bundle\SyncBundle\Authentication\Ticket\TicketDigestStorage\TicketDigestStorageInterface;
use Oro\Bundle\SyncBundle\Authentication\Ticket\TicketProvider;
use Oro\Bundle\SyncBundle\Tests\Unit\Authentication\Ticket\TicketDigestStorage\InMemoryTicketDigestStorageStub;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Security\UserProvider;

class TicketProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var TicketProvider */
    private $ticketProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $tokenStorage;

    /** @var TicketDigestStorageInterface */
    private $ticketDigestStorage;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $userProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $logger;

    protected function setUp()
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->ticketDigestStorage = new InMemoryTicketDigestStorageStub();
        $this->userProvider = $this->createMock(UserProvider::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->ticketProvider = new TicketProvider(
            $this->tokenStorage,
            $this->ticketDigestStorage,
            $this->userProvider,
            $this->logger,
            'test_secret'
        );
    }

    public function testGenerateTicketForAnonymousUser()
    {
        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Generate Sync ticket');

        $ticket = $this->ticketProvider->generateTicket(true);

        list($ticketId, $userName, $nonce, $created) = explode(';', $ticket);
        $this->assertEmpty($userName);
        $digest = $this->ticketDigestStorage->getTicketDigest($ticketId);
        $this->assertNotEmpty($digest);
        $expectedDigest = $this->generateDigest($nonce, $created, 'test_secret');
        $this->assertEquals($expectedDigest, $digest);
    }

    public function testGenerateTicketForNonAnonymousUser()
    {
        $user = new User();
        $user->setUsername('test_user');
        $user->setPassword('test_password');

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Generate Sync ticket');

        $ticket = $this->ticketProvider->generateTicket();

        list($ticketId, $userName, $nonce, $created) = explode(';', $ticket);
        $this->assertEquals('test_user', $userName);
        $digest = $this->ticketDigestStorage->getTicketDigest($ticketId);
        $this->assertNotEmpty($digest);
        $expectedDigest = $this->generateDigest($nonce, $created, 'test_password');
        $this->assertEquals($expectedDigest, $digest);
    }

    public function testIsTicketValidForNotAnonymousToken()
    {
        $user = new User();
        $user->setUsername('test_user');
        $user->setPassword('test_password');

        $nonce = uniqid('', true);
        $created = date('c');

        $ticketId = $this->ticketDigestStorage->saveTicketDigest(
            $this->generateDigest($nonce, $created, 'test_password')
        );
        $ticket = sprintf('%s;%s;%s;%s', $ticketId, 'test_user', $nonce, $created);

        $this->userProvider->expects($this->once())
            ->method('loadUserByUsername')
            ->with('test_user')
            ->willReturn($user);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Ticket is valid');

        $this->assertTrue($this->ticketProvider->isTicketValid($ticket));
    }

    public function testIsTicketValidForAnonymousToken()
    {
        $nonce = uniqid('', true);
        $created = date('c');

        $ticketId = $this->ticketDigestStorage->saveTicketDigest(
            $this->generateDigest($nonce, $created, 'test_secret')
        );
        $ticket = sprintf('%s;%s;%s;%s', $ticketId, '', $nonce, $created);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Ticket is valid');

        $this->assertTrue($this->ticketProvider->isTicketValid($ticket));
    }

    public function testIsTicketValidForTicketWithInvalidDate()
    {
        $date = new \DateTime();
        $date->add(new \DateInterval('P10D'));
        $ticketId = uniqid('', true);

        $ticket = sprintf('%s;;;%s', $ticketId, $date->format('c'));

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Ticket is not valid, because it have created date from the future');

        $this->assertFalse($this->ticketProvider->isTicketValid($ticket));
    }

    public function testIsTicketValidForTicketWithoutSavedDigest()
    {
        $created = date('c');
        $ticketId = uniqid('', true);

        $ticket = sprintf('%s;;;%s', $ticketId, $created);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Ticket is not valid, because we have no saved Digest for it');

        $this->assertFalse($this->ticketProvider->isTicketValid($ticket));
    }

    public function testIsTicketValidWithNotSavedDigest()
    {
        $nonce = uniqid('', true);
        $created = date('c');

        $ticketId = uniqid('', true);
        $ticket = sprintf('%s;%s;%s;%s', $ticketId, 'test_user', $nonce, $created);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Ticket is not valid, because we have no saved Digest for it');

        $this->assertFalse($this->ticketProvider->isTicketValid($ticket));
    }

    public function testIsTicketValidForNotValidUser()
    {
        $nonce = uniqid('', true);
        $created = date('c');

        $ticketId = $this->ticketDigestStorage->saveTicketDigest(
            $this->generateDigest($nonce, $created, 'test_password')
        );
        $ticket = sprintf('%s;%s;%s;%s', $ticketId, 'test_user', $nonce, $created);

        $this->userProvider->expects($this->once())
            ->method('loadUserByUsername')
            ->with('test_user')
            ->willThrowException(new UsernameNotFoundException());

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Ticket is not valid, because there is no user with username "{userName}"');

        $this->assertFalse($this->ticketProvider->isTicketValid($ticket));
    }

    public function testIsTicketValidForInvalidDigest()
    {
        $nonce = uniqid('', true);
        $created = date('c');

        $ticketId = $this->ticketDigestStorage->saveTicketDigest('notValidDigest');
        $ticket = sprintf('%s;%s;%s;%s', $ticketId, '', $nonce, $created);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Ticket is not valid, because Digest is not valid');

        $this->assertFalse($this->ticketProvider->isTicketValid($ticket));
    }

    public function testGeneratedTicketIsValidForAnonymousUser()
    {
        $this->logger->expects($this->at(0))
            ->method('debug')
            ->with('Generate Sync ticket');

        $this->logger->expects($this->at(1))
            ->method('debug')
            ->with('Ticket is valid');

        $ticket = $this->ticketProvider->generateTicket(true);
        $this->assertTrue($this->ticketProvider->isTicketValid($ticket));
    }

    public function testGeneratedTicketIsValidForNonAnonymousUser()
    {
        $user = new User();
        $user->setUsername('test_user');
        $user->setPassword('test_password');

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->logger->expects($this->at(0))
            ->method('debug')
            ->with('Generate Sync ticket');

        $this->userProvider->expects($this->once())
            ->method('loadUserByUsername')
            ->with('test_user')
            ->willReturn($user);

        $this->logger->expects($this->at(1))
            ->method('debug')
            ->with('Ticket is valid');

        $ticket = $this->ticketProvider->generateTicket();
        $this->assertTrue($this->ticketProvider->isTicketValid($ticket));
    }

    private function generateDigest($nonce, $created, $password, $salt = 'test_secret')
    {
        $encoder = new MessageDigestPasswordEncoder();
        return $encoder->encodePassword(
            sprintf('%s%s%s', base64_decode($nonce), $created, $password),
            $salt
        );
    }
}
