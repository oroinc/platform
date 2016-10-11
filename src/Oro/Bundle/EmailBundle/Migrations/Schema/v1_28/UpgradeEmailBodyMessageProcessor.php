<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_28;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EmailBundle\Tools\EmailBodyHelper;
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

    /**
     * @param MessageProducerInterface $messageProducer
     * @param ManagerRegistry          $doctrine
     */
    public function __construct(MessageProducerInterface $messageProducer, ManagerRegistry $doctrine)
    {
        $this->messageProducer = $messageProducer;
        $this->doctrine = $doctrine;
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

        $selectQuery = 'SELECT id, body FROM oro_email_body '
            . 'WHERE body IS NOT NULL AND text_body is NULL AND id BETWEEN :startId AND :endID';

        $updateQuery = 'update oro_email_body set text_body = :textBody where id = :id';

        try {
            $connection = $this->doctrine->getConnection();
            $data = $connection
                ->executeQuery(
                    $selectQuery,
                    ['startId' => $startId, 'endID' => $endId],
                    ['startId' => 'integer', 'endID' => 'integer']
                )
                ->fetchAll();

            foreach ($data as $dataArray) {
                $connection->executeQuery(
                    $updateQuery,
                    ['id' => $dataArray['id'], 'textBody' => $emailBodyHelper->getTrimmedClearText($dataArray['body'])],
                    ['id' => 'integer', 'textBody' => 'string']
                );
            }
        } catch (\Exception $e) {
            // in case if something goes wrong - requeue current process
            return self::REQUEUE;
        }

        return self::ACK;
    }
}
