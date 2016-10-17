<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_29;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;

use Oro\Bundle\EmailBundle\Tools\EmailBodyHelper;
use Oro\Bundle\EntityBundle\ORM\NativeQueryExecutorHelper;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class UpgradeEmailBodyMessageProcessor implements MessageProcessorInterface
{
    const TOPIC_NAME = 'oro_email.migrate_email_body';
    const BATCH_SIZE = 500;

    /** @var MessageProducerInterface */
    protected $messageProducer;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var NativeQueryExecutorHelper */
    protected $queryHelper;

    /**
     * @param MessageProducerInterface  $messageProducer
     * @param ManagerRegistry           $doctrine
     * @param NativeQueryExecutorHelper $queryHelper
     */
    public function __construct(
        MessageProducerInterface $messageProducer,
        ManagerRegistry $doctrine,
        NativeQueryExecutorHelper $queryHelper
    ) {
        $this->messageProducer = $messageProducer;
        $this->doctrine = $doctrine;
        $this->queryHelper = $queryHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        if ($message->getBody() !== '') {
            return $this->processBatch((int)$message->getBody());
        }

        $this->scheduleMigrateProcesses();

        return self::ACK;
    }

    /**
     * Split work to batches
     */
    protected function scheduleMigrateProcesses()
    {
        /** @var Connection $connection */
        $connection = $this->doctrine->getConnection();
        $maxItemNumber = $connection
            ->executeQuery(
                'select max(id) from oro_email_body'
            )
            ->fetchColumn();
        $jobsCount = floor((int)$maxItemNumber / self::BATCH_SIZE);
        for ($i = 0; $i <= $jobsCount; $i++) {
            $this->messageProducer->send(self::TOPIC_NAME, $i);
        }
    }

    /**
     * Process one data batch
     *
     * @param integer $pageNumber
     *
     * @return string
     */
    protected function processBatch($pageNumber)
    {
        $emailBodyHelper = new EmailBodyHelper();

        $startId = self::BATCH_SIZE * $pageNumber;
        $endId = $startId + self::BATCH_SIZE;

        $tableName = $this->queryHelper->getTableName('Oro\Bundle\EmailBundle\Entity\EmailBody');

        $selectQuery = 'SELECT id, body FROM '
            . $tableName
            . 'WHERE body IS NOT NULL AND text_body is NULL AND id BETWEEN :startId AND :endID';


        try {
            /** @var Connection $connection */
            $connection = $this->doctrine->getConnection();
            $data = $connection
                ->fetchAll(
                    $selectQuery,
                    ['startId' => $startId, 'endID' => $endId],
                    ['startId' => 'integer', 'endID' => 'integer']
                );

            foreach ($data as $dataArray) {
                $connection->update(
                    $tableName,
                    ['text_body' => $emailBodyHelper->getTrimmedClearText($dataArray['body'])],
                    ['id' => $dataArray['id']],
                    ['textBody' => 'string']
                );
            }
        } catch (\Exception $e) {
            // in case if something goes wrong - requeue current process
            return self::REQUEUE;
        }

        return self::ACK;
    }
}
