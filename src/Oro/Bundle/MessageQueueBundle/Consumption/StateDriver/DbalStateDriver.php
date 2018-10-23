<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\StateDriver;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\MessageQueueBundle\Consumption\StateDriverInterface;
use Psr\Log\LoggerInterface;

/**
 * The state driver that uses the database as a storage.
 */
class DbalStateDriver implements StateDriverInterface
{
    /** @var string */
    private $key;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param string          $key
     * @param ManagerRegistry $doctrine
     * @param LoggerInterface $logger
     */
    public function __construct($key, ManagerRegistry $doctrine, LoggerInterface $logger)
    {
        $this->key = $key;
        $this->doctrine = $doctrine;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function setChangeStateDate(\DateTime $date = null)
    {
        try {
            $this->saveChangeStateDate($date);
        } catch (DriverException $e) {
            // ignore an unexpected exception from the database,
            // e.g. when a server closed the connection unexpectedly
            $this->logger->error(
                'Cannot save the cache state date into the database.',
                ['exception' => $e, 'date' => $date]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getChangeStateDate()
    {
        try {
            return $this->loadChangeStateDate();
        } catch (DriverException $e) {
            // ignore an unexpected exception from the database,
            // e.g. when a server closed the connection unexpectedly
            $this->logger->error(
                'Cannot load the cache state date from the database.',
                ['exception' => $e]
            );

            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setChangeStateDateWithTimeGap(\DateTime $date, $gapPeriod = 5)
    {
        try {
            $this->saveChangeStateDateWithTimeGap($date, $gapPeriod);
        } catch (DriverException $e) {
            // ignore an unexpected exception from the database,
            // e.g. when a server closed the connection unexpectedly
            $this->logger->error(
                'Cannot save the cache state date into the database.',
                ['exception' => $e, 'date' => $date]
            );
        }
    }

    /**
     * @param \DateTime $date
     * @param integer $gapPeriod
     */
    private function saveChangeStateDateWithTimeGap(\DateTime $date, $gapPeriod)
    {
        $dateWithGap = clone $date;
        $dateWithGap->sub(new \DateInterval('PT' . $gapPeriod . 'M'));

        $querySQL = 'UPDATE oro_message_queue_state SET updated_at = :updatedAt'
            . ' WHERE id = :id AND updated_at < :dateWithGap';

        $this->getConnection()->executeUpdate(
            $querySQL,
            ['updatedAt' => $date, 'id' => $this->key, 'dateWithGap' => $dateWithGap],
            ['updatedAt' => Type::DATETIME, 'id' => Type::STRING, 'dateWithGap' => Type::DATETIME]
        );
    }

    /**
     * @return Connection
     */
    private function getConnection()
    {
        return $this->doctrine->getConnection();
    }

    /**
     * @param \DateTime|null $date
     */
    private function saveChangeStateDate(\DateTime $date = null)
    {
        $this->getConnection()->update(
            'oro_message_queue_state',
            ['updated_at' => $date],
            ['id' => $this->key],
            ['updated_at' => 'datetime']
        );
    }

    /**
     * @return \DateTime|null
     */
    private function loadChangeStateDate()
    {
        $result = $this->getConnection()->createQueryBuilder()
            ->from('oro_message_queue_state')
            ->select('updated_at')
            ->where('id = :id')
            ->setParameter('id', $this->key, \PDO::PARAM_STR)
            ->execute()
            ->fetchColumn();

        if ($result) {
            $result = new \DateTime($result, new \DateTimeZone('UTC'));
        }

        return $result;
    }
}
