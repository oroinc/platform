<?php
namespace Oro\Bundle\IntegrationBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\IntegrationBundle\Manager\GenuineSyncScheduler;
use Oro\Component\MessageQueue\Job\Job;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Runs synchronization for integration
 */
class SyncCommand extends Command implements CronCommandInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getDefaultDefinition()
    {
        return '*/5 * * * *';
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        /** @var ChannelRepository $integrationRepository */
        $integrationRepository = $this->getEntityManager()->getRepository(Integration::class);
        $qb = $integrationRepository
            ->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.transport is NOT NULL')
            ->andWhere('c.enabled = :isEnabled')
            ->setParameter('isEnabled', true);

        $count = $qb->getQuery()->getSingleScalarResult();

        return ($count > 0);
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('oro:cron:integration:sync')
            ->addOption(
                'integration',
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
        $connector = $input->getOption('connector');
        $integrationId = $input->getOption('integration');
        $connectorParameters = $this->getConnectorParameters($input);

        /** @var ChannelRepository $integrationRepository */
        $integrationRepository = $this->getEntityManager()->getRepository(Integration::class);

        if ($integrationId) {
            $integration = $integrationRepository->getOrLoadById($integrationId);
            if (! $integration) {
                throw new \LogicException(sprintf('Integration with id "%s" is not found', $integrationId));
            }

            $integrations = [$integration];
        } else {
            $integrations = $integrationRepository->getConfiguredChannelsForSync(null, true);
        }

        $jobProcessor = $this->container->get('oro_message_queue.job.processor');
        $translator = $this->container->get('translator');

        /* @var Integration $integration */
        foreach ($integrations as $integration) {
            $output->writeln(sprintf('Schedule sync for "%s" integration.', $integration->getName()));

            // check if the integration job with `new` or `in progress` status already exists.
            // Temporary solution. should be refacored during BAP-14803.
            $jobName = 'oro_integration:sync_integration:'.$integration->getId();
            $existingJob = $jobProcessor->findNotStaleRootJobyJobNameAndStatuses(
                $jobName,
                [Job::STATUS_NEW, Job::STATUS_RUNNING]
            );
            if ($existingJob) {
                $output->writeln(
                    sprintf(
                        'Skip new sync for "%s" integration because such job already exists with "%s" status',
                        $integration->getName(),
                        $translator->trans($existingJob->getStatus())
                    )
                );

                continue;
            }

            $this->getSyncScheduler()->schedule($integration->getId(), $connector, $connectorParameters);
        }
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

    /**
     * @return GenuineSyncScheduler
     */
    private function getSyncScheduler()
    {
        return $this->container->get('oro_integration.genuine_sync_scheduler');
    }

    /**
     * @return EntityManagerInterface
     */
    private function getEntityManager()
    {
        return $this->container->get('doctrine.orm.entity_manager');
    }
}
