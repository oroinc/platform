<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_29;

use Doctrine\DBAL\Connection;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Tools\EmailBodyHelper;
use Oro\Bundle\EntityBundle\ORM\NativeQueryExecutorHelper;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class UpgradeEmailBodyMessageProcessor implements MessageProcessorInterface
{
    const TOPIC_NAME = 'oro_email.migrate_email_body';
    const BATCH_SIZE = 500;

    /** @var MessageProducerInterface */
    protected $messageProducer;

    /** @var NativeQueryExecutorHelper */
    protected $queryHelper;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param MessageProducerInterface  $messageProducer
     * @param NativeQueryExecutorHelper $queryHelper
     */
    public function __construct(
        MessageProducerInterface $messageProducer,
        NativeQueryExecutorHelper $queryHelper,
        LoggerInterface $logger
    ) {
        $this->messageProducer = $messageProducer;
        $this->queryHelper = $queryHelper;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        if ($message->getBody() !== '') {
            // we have page number we should process, so now process this page
            $this->processBatch((int)$message->getBody());
        } else {
            // we have no page number we should process, so now split work to batches
            $this->scheduleMigrateProcesses();
        }

        return self::ACK;
    }

    /**
     * Split work to batches
     */
    protected function scheduleMigrateProcesses()
    {
        /** @var Connection $connection */
        $connection = $this->queryHelper->getManager(EmailBody::class)->getConnection();
        $maxItemNumber = $connection
            ->fetchColumn(
                sprintf(
                    'select max(id) from %s',
                    $this->queryHelper->getTableName(EmailBody::class)
                )
            );
        $jobsCount = floor((int)$maxItemNumber / self::BATCH_SIZE);
        for ($i = 0; $i <= $jobsCount; $i++) {
            $this->messageProducer->send(self::TOPIC_NAME, $i);
        }
    }

    /**
     * Process one data batch
     *
     * @param integer $pageNumber
     */
    protected function processBatch($pageNumber)
    {
        $emailBodyHelper = new EmailBodyHelper();

        $startId = self::BATCH_SIZE * $pageNumber;
        $endId = $startId + self::BATCH_SIZE;

        $tableName = $this->queryHelper->getTableName(EmailBody::class);

        $selectQuery = 'SELECT id, body FROM ' . $tableName
            . ' WHERE body IS NOT NULL AND text_body is NULL AND id BETWEEN :startId AND :endID';

        /** @var Connection $connection */
        $connection = $this->queryHelper->getManager(EmailBody::class)->getConnection();
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
    }
}
