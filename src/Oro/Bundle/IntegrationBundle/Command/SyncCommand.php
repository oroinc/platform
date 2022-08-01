<?php
declare(strict_types=1);

namespace Oro\Bundle\IntegrationBundle\Command;

use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CronBundle\Command\CronCommandActivationInterface;
use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\IntegrationBundle\Manager\GenuineSyncScheduler;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobProcessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Schedules synchronization for integrations.
 */
class SyncCommand extends Command implements
    CronCommandScheduleDefinitionInterface,
    CronCommandActivationInterface
{
    /** @var string */
    protected static $defaultName = 'oro:cron:integration:sync';

    private JobProcessor $jobProcessor;
    private TranslatorInterface $translator;
    private GenuineSyncScheduler $syncScheduler;
    private ObjectManager $entityManager;

    public function __construct(
        JobProcessor $jobProcessor,
        TranslatorInterface $translator,
        GenuineSyncScheduler $syncScheduler,
        ObjectManager $objectManager
    ) {
        $this->jobProcessor = $jobProcessor;
        $this->translator = $translator;
        $this->syncScheduler = $syncScheduler;
        $this->entityManager = $objectManager;
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultDefinition(): string
    {
        return '*/5 * * * *';
    }

    /**
     * {@inheritDoc}
     */
    public function isActive(): bool
    {
        /** @var ChannelRepository $integrationRepository */
        $integrationRepository = $this->entityManager->getRepository(Integration::class);
        $qb = $integrationRepository
            ->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.transport is NOT NULL')
            ->andWhere('c.enabled = :isEnabled')
            ->andWhere('c.connectors <> :emptyConnectors')
            ->setParameter('isEnabled', true, Types::BOOLEAN)
            ->setParameter('emptyConnectors', [], Types::ARRAY);

        $count = $qb->getQuery()->getSingleScalarResult();

        return ($count > 0);
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function configure()
    {
        $this
            ->addArgument(
                'connector-parameters',
                InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                'Connector parameters',
                []
            )
            ->addOption('integration', 'i', InputOption::VALUE_OPTIONAL, 'Integration ID')
            ->addOption('connector', 'con', InputOption::VALUE_OPTIONAL, 'Connector name')
            ->setDescription('Schedules synchronization for integrations.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command schedules synchronization for all active integrations.
This command only schedules the job by adding a message to the message queue, so ensure
that the message consumer processes (<info>oro:message-queue:consume</info>) are running
or the scheduled synchronization(s) will not be performed otherwise.

  <info>php %command.full_name%</info>

Any additional integration connector parameters can be passed as arguments
using <comment>name=value</comment> format:

  <info>php %command.full_name% --integration=<ID> param1=value1 param2=value2 paramN=valueN</info>

The <info>--connector</info> option can be used to limit the scope of synchronization to
a specific connector within an integration (all connectors are processed otherwise):

  <info>php %command.full_name% --integration=<ID> --connector=<connector-name></info>

HELP
            )
            ->addUsage('--integration=<ID>')
            ->addUsage('--integration=<ID> param1=value1 param2=value2 paramN=valueN')
            ->addUsage('--connector=<connector-name>')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Started integration sync scheduling');
        $connector = $input->getOption('connector');
        $integrationId = $input->getOption('integration');
        $connectorParameters = $this->getConnectorParameters($input);

        /** @var ChannelRepository $integrationRepository */
        $integrationRepository = $this->entityManager->getRepository(Integration::class);

        if ($integrationId) {
            $integration = $integrationRepository->getOrLoadById($integrationId);
            if (! $integration) {
                throw new \LogicException(sprintf('Integration with id "%s" is not found', $integrationId));
            }

            $integrations = [$integration];
        } else {
            $integrations = $integrationRepository->getConfiguredChannelsForSync(null, true);
        }

        /* @var Integration $integration */
        foreach ($integrations as $integration) {
            $output->writeln(sprintf('Schedule sync for "%s" integration.', $integration->getName()));

            // check if the integration job with `new` or `in progress` status already exists.
            // Temporary solution. should be refacored during BAP-14803.
            $jobName = 'oro_integration:sync_integration:'.$integration->getId();
            $existingJob = $this->jobProcessor->findNotStaleRootJobyJobNameAndStatuses(
                $jobName,
                [Job::STATUS_NEW, Job::STATUS_RUNNING]
            );
            if ($existingJob) {
                $output->writeln(
                    sprintf(
                        'Skip new sync for "%s" integration because such job already exists with "%s" status',
                        $integration->getName(),
                        $this->translator->trans($existingJob->getStatus())
                    )
                );

                continue;
            }

            $this->syncScheduler->schedule($integration->getId(), $connector, $connectorParameters);
        }
        $output->writeln('Integration sync scheduling complete');

        return 0;
    }

    /**
     * Get connector additional parameters array from the input
     *
     * @return array key - parameter name, value - parameter value
     *
     * @throws \LogicException
     */
    protected function getConnectorParameters(InputInterface $input): array
    {
        $result = [];
        $connectorParameters = $input->getArgument('connector-parameters');
        if (!empty($connectorParameters)) {
            foreach ($connectorParameters as $parameterString) {
                $parameterConfigArray = explode('=', $parameterString);
                if (!isset($parameterConfigArray[1])) {
                    throw new \LogicException(sprintf(
                        'Connector parameters should be in "parameterKey=parameterValue" format. Got "%s".',
                        $parameterString
                    ));
                }
                $result[$parameterConfigArray[0]] = $parameterConfigArray[1];
            }
        }

        return $result;
    }
}
