<?php
namespace Oro\Component\MessageQueue\Consumption\Dbal\Extension;

use Doctrine\DBAL\Types\Type;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Transport\Dbal\DbalSession;

class RedeliverOrphanMessagesDbalExtension extends AbstractExtension
{
    /**
     * @var int
     */
    private $orphanTime;

    /**
     * @var int
     */
    private $checkInterval = 60; // 1 min

    /**
     * @var int
     */
    private $lastCheckTime = 0;

    /**
     * @param $orphanTime
     */
    public function __construct($orphanTime = 300)
    {
        $this->orphanTime = $orphanTime;
    }

    /**
     * {@inheritdoc}
     */
    public function onBeforeReceive(Context $context)
    {
        /** @var DbalSession $session */
        $session = $context->getSession();
        if (false == $session instanceof DbalSession) {
            return;
        }

        if (false == $this->shouldCheck()) {
            return;
        }

        $connection = $session->getConnection();
        $dbal = $connection->getDBALConnection();

        $sql = sprintf(
            'UPDATE %s SET consumer_id=NULL, delivered_at=NULL, redelivered=:isRedelivered '.
            'WHERE delivered_at <= :deliveredAt AND consumer_id=:consumerId',
            $connection->getTableName()
        );

        $affectedRows = $dbal->executeUpdate(
            $sql,
            [
                'isRedelivered' => true,
                'deliveredAt' => time() - $this->orphanTime,
                'consumerId' => $context->getMessageConsumer()->getId(),
            ],
            [
                'isRedelivered' => Type::BOOLEAN,
                'deliveredAt' => Type::INTEGER,
                'consumerId' => Type::STRING,
            ]
        );

        if ($affectedRows) {
            $context->getLogger()->alert(sprintf(
                '[RedeliverOrphanMessagesDbalExtension] Orphans were found and redelivered. number: %d',
                $affectedRows
            ));
        }
    }

    /**
     * @return bool
     */
    protected function shouldCheck()
    {
        $time = time();

        if (($time - $this->lastCheckTime) < $this->checkInterval) {
            return false;
        }

        $this->lastCheckTime = $time;

        return true;
    }
}
