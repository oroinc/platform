<?php

namespace Oro\Bundle\LoggerBundle\Monolog;

use Doctrine\DBAL\Statement;
use Monolog\Handler\AbstractProcessingHandler;
use Oro\Bundle\LoggerBundle\Entity\LogEntry;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * This handler is responsible for saving logs into database
 */
class DBLogsHandler extends AbstractProcessingHandler
{
    use ContainerAwareTrait;

    /** @var Statement */
    private $statement;

    /** @var string */
    private $dateTimeFormatString;

    /**
     * {@inheritdoc}
     */
    protected function write(array $record): void
    {
        $formatted = $record['formatted'];

        $this->getPreparedStatement()->execute([
            'message' => $formatted['message'],
            'context' => \json_encode($formatted['context']),
            'level' => $record['level'],
            'channel' => $record['channel'],
            'datetime' => $this->formatDateTime($record['datetime']),
            'extra' => \json_encode($formatted['extra'])
        ]);
    }

    /**
     * @return Statement
     */
    protected function getPreparedStatement()
    {
        if (!$this->statement) {
            $this->statement = $this->container
                ->get('oro_entity.doctrine_helper')
                ->getEntityManagerForClass(LogEntry::class)
                ->getConnection()
                ->prepare('
                    INSERT INTO oro_logger_log_entry(message, context, level, channel, datetime, extra)
                    VALUES (:message, :context, :level, :channel, :datetime, :extra)
            ');
        }
        return $this->statement;
    }

    /**
     * @param \DateTime $dateTime
     * @return string
     */
    protected function formatDateTime(\DateTimeInterface $dateTime)
    {
        if (!$this->dateTimeFormatString) {
            $this->dateTimeFormatString = $this->container
                ->get('oro_entity.doctrine_helper')
                ->getEntityManagerForClass(LogEntry::class)
                ->getConnection()
                ->getDatabasePlatform()
                ->getDateTimeFormatString();
        }
        return $dateTime->format($this->dateTimeFormatString);
    }
}
