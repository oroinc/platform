<?php

namespace Oro\Bundle\EmailBundle\EmailSyncCredentials\Driver;

use Psr\Log\LoggerInterface;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\EmailBundle\EmailSyncCredentials\WrongCredentialsOriginsDriverInterface;

/**
 * DBAL Storage of wrong credential sync origins.
 */
class DbalWrongCredentialsOriginsDriver implements WrongCredentialsOriginsDriverInterface
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param ManagerRegistry $doctrine
     * @param LoggerInterface $logger
     */
    public function __construct(ManagerRegistry $doctrine, LoggerInterface $logger)
    {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function addOrigin($emailOriginId, $ownerId)
    {
        $this->logger->notice('Invalid sync origin was detected.', ['origin_id' => $emailOriginId]);
        $connection = $this->getConnection();
        $existingRecord = $connection->createQueryBuilder()
            ->select('origin_id')
            ->from('oro_email_wrong_creds_origin')
            ->where('origin_id = :emailOriginId')
            ->andWhere('owner_id = :ownerId')
            ->setParameters(['emailOriginId' => $emailOriginId, 'ownerId' => $ownerId])
            ->execute()
            ->fetchColumn();

        if (!$existingRecord) {
            $connection->insert(
                'oro_email_wrong_creds_origin',
                ['origin_id' => $emailOriginId, 'owner_id' => $ownerId]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAllOrigins()
    {
        $wrongOriginIds = $this->getConnection()->createQueryBuilder()
            ->select('origin_id')
            ->from('oro_email_wrong_creds_origin')
            ->execute()
            ->fetchAll();

        $origins =  $this->getOriginRepository()->createQueryBuilder('o')
            ->where('o.id in (:ids)')
            ->setParameter('ids', $wrongOriginIds)
            ->getQuery()
            ->getResult();

        return $origins;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllOriginsByOwnerId($ownerId = null)
    {
        $request = $this->getConnection()->createQueryBuilder()
            ->select('origin_id')
            ->from('oro_email_wrong_creds_origin');

        if ($ownerId) {
            $request->andWhere('owner_id = :ownerId')
                ->setParameter('ownerId', $ownerId);
        } else {
            $request->andWhere('owner_id is null');
        }

        $wrongOriginIds = $request->execute()
            ->fetchAll();

        $origins =  $this->getOriginRepository()->createQueryBuilder('o')
            ->where('o.id in (:ids)')
            ->setParameter('ids', $wrongOriginIds)
            ->getQuery()
            ->getResult();

        return $origins;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteOrigin($emailOrigin)
    {
        $this->getConnection()->delete('oro_email_wrong_creds_origin', ['origin_id' => $emailOrigin]);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAllOrigins()
    {
        $this->logger->debug('Clear the origins info storage.');
        $this->getConnection()->exec('DELETE FROM oro_email_wrong_creds_origin');
    }

    /**
     * @return Connection
     */
    private function getConnection()
    {
        return $this->doctrine->getConnection();
    }

    /**
     * @return EntityRepository
     */
    private function getOriginRepository()
    {
        return $this->doctrine->getRepository(UserEmailOrigin::class);
    }
}