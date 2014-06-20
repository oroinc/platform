<?php

namespace Oro\Bundle\IntegrationBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\CronBundle\Command\Logger\OutputLogger;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\SyncProcessor;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;

/**
 * Class SyncCommand
 * Console command implementation
 *
 * @package Oro\Bundle\IntegrationBundle\Command
 */
class SyncCommand extends AbstractSyncCronCommand
{
    const COMMAND_NAME = 'oro:cron:integration:sync';

    /**
     * {@inheritdoc}
     */
    public function getDefaultDefinition()
    {
        return '*/5 * * * *';
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName(static::COMMAND_NAME)
            ->addOption(
                'integration-id',
                'i',
                InputOption::VALUE_OPTIONAL,
                'If option exists sync will be performed for given integration id'
            )
            ->addOption(
                'connector',
                'con',
                InputOption::VALUE_OPTIONAL,
                'If option exists sync will be performed for given connector name'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Run sync in force mode, might not be supported by some channel/connector types'
            )
            ->addOption(
                'transport-batch-size',
                'b',
                InputOption::VALUE_REQUIRED,
                'Batch size used in transport layer (value bigger than 100 requires memory limit increase)',
                100
            )
            ->addArgument(
                'connector-parameters',
                InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                'Additional connector parameters array. Format - parameterKey=parameterValue',
                []
            )
            ->setDescription('Runs synchronization for integration');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ChannelRepository $repository */
        /** @var SyncProcessor $processor */
        $connector           = $input->getOption('connector');
        $integrationId       = $input->getOption('integration-id');
        $batchSize           = $input->getOption('transport-batch-size');
        $connectorParameters = $this->getConnectorParameters($input);
        $repository          = $this->getService('doctrine.orm.entity_manager')
            ->getRepository('OroIntegrationBundle:Channel');
        $logger              = new OutputLogger($output);
        $processor           = $this->getService(self::SYNC_PROCESSOR);
        $processor->getLoggerStrategy()->setLogger($logger);

        $this->getContainer()->get('doctrine.orm.entity_manager')
            ->getConnection()->getConfiguration()->setSQLLogger(null);

        if ($this->isJobRunning($integrationId)) {
            $logger->warning('Job already running. Terminating....');

            return 0;
        }

        if ($integrationId) {
            $integration = $repository->getOrLoadById($integrationId);
            if (!$integration) {
                throw new \InvalidArgumentException('Channel with given ID not found');
            }
            $integrations = [$integration];
        } else {
            $integrations = $repository->getConfiguredChannelsForSync();
        }

        /** @var Channel $integration */
        foreach ($integrations as $integration) {
            try {
                $logger->notice(sprintf('Run sync for "%s" channel.', $integration->getName()));

                if ($batchSize) {
                    $integration->getTransport()->getSettingsBag()->set('page_size', $batchSize);
                }
                $processor->process($integration, $connector, $connectorParameters);
            } catch (\Exception $e) {
                $logger->critical($e->getMessage(), ['exception' => $e]);
                //process another channel even in case if exception thrown
                continue;
            }
        }

        $logger->notice('Completed');

        return 0;
    }

    /**
     * Get connector additional parameters array from the input
     *
     * @param InputInterface $input
     *
     * @return array key - parameter name, value - parameter value
     * @throws \LogicException
     */
    protected function getConnectorParameters(InputInterface $input)
    {
        $result              = ['force' => $input->getOption('force')];
        $connectorParameters = $input->getArgument('connector-parameters');
        if (!empty($connectorParameters)) {
            foreach ($connectorParameters as $parameterString) {
                $parameterConfigArray = explode('=', $parameterString);
                if (!isset($parameterConfigArray[1])) {
                    throw new \LogicException('Format for connector parameters is parameterKey=parameterValue');
                }
                $result[$parameterConfigArray[0]] = $parameterConfigArray[1];
            }
        }

        return $result;
    }
}
