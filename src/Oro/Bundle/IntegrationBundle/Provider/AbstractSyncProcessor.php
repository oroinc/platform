<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Psr\Log\NullLogger;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\IntegrationBundle\Entity\Status;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\ImportExport\Job\Executor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;

abstract class AbstractSyncProcessor implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var ProcessorRegistry */
    protected $processorRegistry;

    /** @var Executor */
    protected $jobExecutor;

    /** @var TypesRegistry */
    protected $registry;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @param ProcessorRegistry        $processorRegistry
     * @param Executor                 $jobExecutor
     * @param TypesRegistry            $registry
     * @param EventDispatcherInterface $eventDispatcher
     * @param LoggerStrategy|null      $logger
     */
    public function __construct(
        ProcessorRegistry $processorRegistry,
        Executor $jobExecutor,
        TypesRegistry $registry,
        EventDispatcherInterface $eventDispatcher,
        LoggerStrategy $logger = null
    ) {
        $this->processorRegistry = $processorRegistry;
        $this->jobExecutor       = $jobExecutor;
        $this->registry          = $registry;
        $this->eventDispatcher   = $eventDispatcher;
        $this->setLogger($logger ?: new LoggerStrategy(new NullLogger()));
    }

    /**
     * Get logger strategy
     *
     * @return LoggerStrategy
     */
    public function getLoggerStrategy()
    {
        return $this->logger;
    }

    /**
     * Format result statistic message based on statistic if context were fetched
     *
     * @param ContextInterface $context
     *
     * @return string
     */
    protected function formatResultMessage(ContextInterface $context = null)
    {
        $statistic = $this->fetchStatistic($context);

        return preg_replace_callback(
            '#%(\w+)%#',
            function ($match) use ($statistic) {
                $fieldName = trim(end($match));
                if (isset($statistic[$fieldName])) {
                    return $statistic[$fieldName];
                }

                return 'UNKNOWN';
            },
            'Stats: read [%read%], processed [%processed%], updated [%updated%], added [%added%], ' .
            'deleted [%deleted%], invalid entities: [%invalid%]'
        );
    }

    /**
     * Fetch job execution result statistic from context
     *
     * @param ContextInterface $context
     *
     * @return array
     */
    private function fetchStatistic(ContextInterface $context = null)
    {
        $counts = array_fill_keys(['read', 'processed', 'updated', 'deleted', 'added', 'invalid'], 0);
        if ($context) {
            $counts['read'] = (int)$context->getReadCount();
            $counts['processed'] += $counts['added'] = (int)$context->getAddCount();
            $counts['processed'] += $counts['updated'] = (int)$context->getUpdateCount();
            $counts['processed'] += $counts['deleted'] = (int)$context->getDeleteCount();
            $counts['invalid'] = (int)$context->getErrorEntriesCount();
        }

        return $counts;
    }

    /**
     * Clone object here because it will be modified and changes should not be shared between
     *
     * @param Integration $integration
     * @param string      $connector
     *
     * @return TwoWaySyncConnectorInterface
     */
    protected function getRealConnector(Integration $integration, $connector)
    {
        return clone $this->registry->getConnectorType($integration->getType(), $connector);
    }

    /**
     * @param Status $status
     *
     * @return bool
     */
    protected function isIntegrationConnectorProcessSuccess(Status $status)
    {
        return $status->getCode() == Status::STATUS_COMPLETED;
    }
}
