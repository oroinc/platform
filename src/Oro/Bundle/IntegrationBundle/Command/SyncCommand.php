<?php

namespace Oro\Bundle\IntegrationBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Component\Log\OutputLogger;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
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

    const STATUS_SUCCESS = 0;
    const STATUS_FAILED  = 255;

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
                'Run sync in force mode, might not be supported by some integration/connector types'
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
        if ($output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) {
            $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        }

        /** @var ChannelRepository $repository */
        /** @var SyncProcessor $processor */
        $connector           = $input->getOption('connector');
        $integrationId       = $input->getOption('integration-id');
        $batchSize           = $input->getOption('transport-batch-size');
        $connectorParameters = $this->getConnectorParameters($input);
        $entityManager       = $this->getService('doctrine.orm.entity_manager');
        $repository          = $entityManager->getRepository('OroIntegrationBundle:Channel');
        $logger              = new OutputLogger($output);
        $processor           = $this->getService(self::SYNC_PROCESSOR);
        $exitCode            = self::STATUS_SUCCESS;

        $processor->getLoggerStrategy()->setLogger($logger);
        $entityManager->getConnection()->getConfiguration()->setSQLLogger(null);

        if ($this->isJobRunning($integrationId)) {
            $logger->warning('Job already running. Terminating....');

            return self::STATUS_SUCCESS;
        }

        if ($integrationId) {
            $integration = $repository->getOrLoadById($integrationId);
            if (!$integration) {
                $logger->critical(sprintf('Integration with given ID "%d" not found', $integrationId));

                return self::STATUS_FAILED;
            }
            $integrations = [$integration];
        } else {
            $integrations = $repository->getConfiguredChannelsForSync(null, true);
        }

        /** @var Integration $integration */
        foreach ($integrations as $integration) {
            try {
                $logger->notice(sprintf('Run sync for "%s" integration.', $integration->getName()));

                if ($batchSize) {
                    $integration->getTransport()->getSettingsBag()->set('page_size', $batchSize);
                }

                $result   = $processor->process($integration, $connector, $connectorParameters);
                $exitCode = $result ? : self::STATUS_FAILED;
            } catch (\Exception $e) {
                $logger->critical($e->getMessage(), ['exception' => $e]);

                $exitCode = self::STATUS_FAILED;

                continue;
            }
        }

        $logger->notice('Completed');

        return $exitCode;
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
        $result = ['force' => $input->getOption('force')];

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
