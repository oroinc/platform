<?php

namespace Oro\Bundle\ImapBundle\Tests\Functional\OriginSyncCredentials\Driver;

use Doctrine\DBAL\Connection;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\OriginSyncCredentials\Driver\DbalWrongCredentialsOriginsDriver;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Psr\Log\LoggerInterface;

/**
 * @dbIsolationPerTest
 */
class DbalWrongCredentialsOriginsDriverTest extends WebTestCase
{
    /** @var DbalWrongCredentialsOriginsDriver */
    private $driver;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    protected function setUp()
    {
        $this->initClient();

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->driver = new DbalWrongCredentialsOriginsDriver($this->getContainer()->get('doctrine'), $this->logger);
    }

    public function testAddOrigin()
    {
        $emailOriginId = 12;
        $ownerId = 23;

        $this->logger->expects($this->once())
            ->method('notice')
            ->with('Email origin with wrong credentials was detected.', ['origin_id' => $emailOriginId]);

        $this->driver->addOrigin($emailOriginId, $ownerId);

        /** @var Connection $connection */
        $connection = $this->getContainer()->get('doctrine')->getConnection();

        $dbData = $connection->fetchAll('select origin_id, owner_id from oro_imap_wrong_creds_origin');
        $this->assertEquals(
            [
                ['origin_id' => $emailOriginId, 'owner_id' => $ownerId]
            ],
            $dbData
        );
    }

    public function testAddOriginOnExistingOrigin()
    {
        /** @var Connection $connection */
        $connection = $this->getContainer()->get('doctrine')->getConnection();

        $emailOriginId = 12;
        $ownerId = 23;

        $connection->insert('oro_imap_wrong_creds_origin', ['origin_id'=> $emailOriginId, 'owner_id' => $ownerId]);

        $this->logger->expects($this->once())
            ->method('notice')
            ->with('Email origin with wrong credentials was detected.', ['origin_id' => $emailOriginId]);

        $this->driver->addOrigin($emailOriginId, $ownerId);

        $dbData = $connection->fetchAll('select origin_id, owner_id from oro_imap_wrong_creds_origin');
        $this->assertEquals(
            [
                ['origin_id' => $emailOriginId, 'owner_id' => $ownerId]
            ],
            $dbData
        );
    }

    public function testGetAllOriginsOnEmptyStorage()
    {
        $this->assertEmpty($this->driver->getAllOrigins());
    }

    public function testGetAllOrigins()
    {
        $doctrine = $this->getContainer()->get('doctrine');

        $origin = new UserEmailOrigin();
        $origin->setMailboxName('test');
        $em = $doctrine->getEntityManager();
        $em->persist($origin);
        $em->flush($origin);

        $doctrine->getConnection()->insert('oro_imap_wrong_creds_origin', ['origin_id'=> $origin->getId()]);

        $this->assertEquals([$origin], $this->driver->getAllOrigins());
    }

    public function testGetAllOriginsByOwnerIdOnEmptyStorage()
    {
        $this->assertEmpty($this->driver->getAllOriginsByOwnerId(12));
    }

    public function testGetAllOriginsByOwnerIdWithPassedId()
    {
        $doctrine = $this->getContainer()->get('doctrine');

        $userOrigin = new UserEmailOrigin();
        $userOrigin->setMailboxName('user');

        $systemOrigin = new UserEmailOrigin();
        $systemOrigin->setMailboxName('system');

        $em = $doctrine->getEntityManager();
        $em->persist($userOrigin);
        $em->persist($systemOrigin);
        $em->flush();

        $connection = $doctrine->getConnection();
        $connection->insert('oro_imap_wrong_creds_origin', ['origin_id'=> $userOrigin->getId(), 'owner_id' => 21]);
        $connection->insert('oro_imap_wrong_creds_origin', ['origin_id'=> $systemOrigin->getId()]);

        $this->assertEquals([$userOrigin], $this->driver->getAllOriginsByOwnerId(21));
    }

    public function testGetAllOriginsByOwnerIdWithoutPassedId()
    {
        $doctrine = $this->getContainer()->get('doctrine');

        $userOrigin = new UserEmailOrigin();
        $userOrigin->setMailboxName('user');

        $systemOrigin = new UserEmailOrigin();
        $systemOrigin->setMailboxName('system');

        $em = $doctrine->getEntityManager();
        $em->persist($userOrigin);
        $em->persist($systemOrigin);
        $em->flush();

        $connection = $doctrine->getConnection();
        $connection->insert('oro_imap_wrong_creds_origin', ['origin_id'=> $userOrigin->getId(), 'owner_id' => 21]);
        $connection->insert('oro_imap_wrong_creds_origin', ['origin_id'=> $systemOrigin->getId()]);

        $this->assertEquals([$systemOrigin], $this->driver->getAllOriginsByOwnerId());
    }

    public function testDeleteOrigin()
    {
        $connection = $this->getContainer()->get('doctrine')->getConnection();
        $connection->insert('oro_imap_wrong_creds_origin', ['origin_id'=> 52, 'owner_id' => 21]);
        $connection->insert('oro_imap_wrong_creds_origin', ['origin_id'=> 45]);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with(
                'Remove email origin from wrong credentials origins info storage.',
                ['origin_id' => 52]
            );

        $this->driver->deleteOrigin(52);

        $dbData = $connection->fetchAll('select origin_id, owner_id from oro_imap_wrong_creds_origin');
        $this->assertEquals(
            [
                ['origin_id' => 45, 'owner_id' => null]
            ],
            $dbData
        );
    }

    public function testDeleteAllOrigins()
    {
        $connection = $this->getContainer()->get('doctrine')->getConnection();
        $connection->insert('oro_imap_wrong_creds_origin', ['origin_id'=> 52, 'owner_id' => 21]);
        $connection->insert('oro_imap_wrong_creds_origin', ['origin_id'=> 45]);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Delete email origins with wrong credentials from the storage.');

        $this->driver->deleteAllOrigins();

        $dbData = $connection->fetchAll('select origin_id, owner_id from oro_imap_wrong_creds_origin');
        $this->assertEmpty($dbData);
    }
}
