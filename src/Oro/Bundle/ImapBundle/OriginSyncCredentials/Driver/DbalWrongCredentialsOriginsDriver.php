<?php

namespace Oro\Bundle\ImapBundle\OriginSyncCredentials\Driver;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ImapBundle\Entity\Repository\UserEmailOriginRepository;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\OriginSyncCredentials\WrongCredentialsOriginsDriverInterface;
use Psr\Log\LoggerInterface;

/**
 * DBAL Storage of wrong credential sync origins.
 */
class DbalWrongCredentialsOriginsDriver implements WrongCredentialsOriginsDriverInterface
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(ManagerRegistry $doctrine, LoggerInterface $logger)
    {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
    }

    #[\Override]
    public function addOrigin($emailOriginId, $ownerId = null)
    {
        $this->logger->notice('Email origin with wrong credentials was detected.', ['origin_id' => $emailOriginId]);
        $connection = $this->getConnection();
        $request = $connection->createQueryBuilder()
            ->select('origin_id')
            ->from('oro_imap_wrong_creds_origin')
            ->where('origin_id = :emailOriginId');
        if ($ownerId) {
            $request->andWhere('owner_id = :ownerId')
                ->setParameter('ownerId', $ownerId);
        } else {
            $request->andWhere('owner_id is null');
        }

        $existingRecord = $request
            ->setParameter('emailOriginId', $emailOriginId)
            ->executeQuery()
            ->fetchOne();

        if (!$existingRecord) {
            $connection->insert(
                'oro_imap_wrong_creds_origin',
                ['origin_id' => $emailOriginId, 'owner_id' => $ownerId]
            );
        }
    }

    #[\Override]
    public function getAllOrigins()
    {
        $origins = [];

        $wrongOriginIds = $this->getConnection()->createQueryBuilder()
            ->select('origin_id')
            ->from('oro_imap_wrong_creds_origin')
            ->executeQuery()
            ->fetchAllAssociative();

        if (count($wrongOriginIds)) {
            $origins = $this->getOriginRepository()->getOriginsByIds($wrongOriginIds);
        }

        return $origins;
    }

    #[\Override]
    public function getAllOriginsByOwnerId($ownerId = null)
    {
        $origins = [];

        $request = $this->getConnection()->createQueryBuilder()
            ->select('origin_id')
            ->from('oro_imap_wrong_creds_origin');

        if ($ownerId) {
            $request->andWhere('owner_id = :ownerId')
                ->setParameter('ownerId', $ownerId);
        } else {
            $request->andWhere('owner_id is null');
        }

        $wrongOriginIds = $request->executeQuery()->fetchAllAssociative();

        if (count($wrongOriginIds)) {
            $origins = $this->getOriginRepository()->getOriginsByIds($wrongOriginIds);
        }

        return $origins;
    }

    #[\Override]
    public function deleteOrigin($emailOriginId)
    {
        $this->logger->debug(
            'Remove email origin from wrong credentials origins info storage.',
            ['origin_id' => $emailOriginId]
        );
        $this->getConnection()->delete('oro_imap_wrong_creds_origin', ['origin_id' => $emailOriginId]);
    }

    #[\Override]
    public function deleteAllOrigins()
    {
        $this->logger->debug('Delete email origins with wrong credentials from the storage.');
        $this->getConnection()->executeStatement('DELETE FROM oro_imap_wrong_creds_origin');
    }

    /**
     * @return Connection
     */
    private function getConnection()
    {
        return $this->doctrine->getConnection();
    }

    /**
     * @return UserEmailOriginRepository
     */
    private function getOriginRepository()
    {
        return $this->doctrine->getRepository(UserEmailOrigin::class);
    }
}
