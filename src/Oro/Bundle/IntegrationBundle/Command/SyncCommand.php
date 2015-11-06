<?php

namespace Oro\Bundle\IntegrationBundle\Command;

use JMS\JobQueueBundle\Entity\Job;

use Psr\Log\LoggerInterface;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\IntegrationBundle\Manager\BlockingJob;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\IntegrationBundle\Provider\AbstractSyncProcessor;
use Oro\Bundle\IntegrationBundle\Provider\SyncProcessorRegistry;
use Oro\Bundle\IntegrationBundle\Provider\SyncProcessor;
use Oro\Bundle\SecurityBundle\Authentication\Token\ConsoleToken;
use Oro\Component\Log\OutputLogger;

/**
 * Class SyncCommand
 * Console command implementation
 *
 * @package Oro\Bundle\IntegrationBundle\Command
 */
class SyncCommand extends AbstractSyncCronCommand
{
    const COMMAND_NAME = 'oro:cron:integration:sync';
    const SYNC_PROCESSOR_REGISTRY = 'oro_integration.processor_registry';

    const STATUS_SUCCESS = 0;
    const STATUS_FAILED = 255;

    /**
     * @var SyncProcessorRegistry
     */
    protected $processorRegistry;

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
        /** @var BlockingJob $blockedJobsManager */
        $connector = $input->getOption('connector');
        $integrationId = $input->getOption('integration-id');
        $batchSize = $input->getOption('transport-batch-size');
        $connectorParameters = $this->getConnectorParameters($input);
        $entityManager = $this->getService('doctrine.orm.entity_manager');
        $repository = $entityManager->getRepository('OroIntegrationBundle:Channel');
        $logger = new OutputLogger($output);
        $entityManager->getConnection()->getConfiguration()->setSQLLogger(null);
        $blockedJobsManager = $this->getService('oro_integration.manager.blocking_job');

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

            if ($this->isBlockingJobRunning($integration, $blockedJobsManager)) {
                $logger->warning('Blocking jobs already running. Terminating....');

                return self::STATUS_SUCCESS;
            }

            $exitCode = $this->processIntegration($logger, $integration, $batchSize, $connector, $connectorParameters);
        } else {
            $integrations = $repository->getConfiguredChannelsForSync(null, true);
            $exitCode = $this->processIntegrations($integrations);
        }

        $logger->notice('Completed');

        return $exitCode;
    }

    /**
     * @param Integration $integration
     * @param BlockingJob $blockingJobManager
     *
     * @return bool
     */
    protected function isBlockingJobRunning(Integration $integration, BlockingJob $blockingJobManager)
    {
        if ($blockingJobManager->hasBlockingJobs($integration->getType())) {
            $commandNames = $blockingJobManager->getBlockingJobs($integration->getType());
            $commandNames = $commandNames->getCommandName();
            $managerRegistry = $this->getService('doctrine');

            /** @var ManagerRegistry $managerRegistry */
            $running = $managerRegistry->getRepository('OroIntegrationBundle:Channel')
                ->getRunningSyncJobsCountByCommands($commandNames);

            if ($running > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param OutputLogger $logger
     * @param Integration $integration
     * @param int|null $batchSize
     * @param $connector
     * @param $connectorParameters
     *
     * @return int
     */
    protected function processIntegration(
        OutputLogger $logger,
        Integration $integration,
        $batchSize,
        $connector,
        $connectorParameters
    ) {
        try {
            $logger->notice(sprintf('Run sync for "%s" integration.', $integration->getName()));
            $this->updateToken($integration);
            if ($batchSize) {
                $integration->getTransport()->getSettingsBag()->set('page_size', $batchSize);
            }
            $processor = $this->getSyncProcessor($integration, $logger);
            $result = $processor->process($integration, $connector, $connectorParameters);
            $exitCode = $result ?: self::STATUS_FAILED;
        } catch (\Exception $e) {
            $logger->critical($e->getMessage(), ['exception' => $e]);
            $exitCode = self::STATUS_FAILED;

        }

        return $exitCode;
    }

    /**
     * @param Integration[] $integrations
     *
     * @return int
     */
    protected function processIntegrations($integrations)
    {
        /** @var ManagerRegistry $managerRegistry */
        $managerRegistry = $this->getService('doctrine');
        foreach ($integrations as $integration) {
            $integrationId = sprintf('--%s=%s', 'integration-id', $integration->getId());
            $jobArgs = [$integrationId];
            $job = new Job(self::COMMAND_NAME, $jobArgs, true);
            $managerRegistry->getManager()->persist($job);
        }
        $managerRegistry->getManager()->flush();

        return self::STATUS_SUCCESS;
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

    /**
     * @param Integration $integration
     */
    protected function updateToken(Integration $integration)
    {
        $securityContext = $this->getSecurityContext();
        $token = $securityContext->getToken();
        if (!$token) {
            $token = new ConsoleToken();
            $this->getSecurityContext()->setToken($token);
        }

        $token->setOrganizationContext($integration->getOrganization());
    }

    /**
     * @return SecurityContextInterface
     */
    protected function getSecurityContext()
    {
        return $this->getService('security.context');
    }

    /**
     * @param Integration $integration
     * @param LoggerInterface $logger
     * @return AbstractSyncProcessor
     */
    protected function getSyncProcessor(Integration $integration, $logger)
    {
        if (!$this->processorRegistry) {
            $this->processorRegistry = $this->getService(self::SYNC_PROCESSOR_REGISTRY);
        }

        $processor = $this->processorRegistry->getProcessorForIntegration($integration);
        $processor->getLoggerStrategy()->setLogger($logger);

        return $processor;
    }
}
