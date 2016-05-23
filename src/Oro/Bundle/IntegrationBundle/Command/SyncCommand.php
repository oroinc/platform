<?php
namespace Oro\Bundle\IntegrationBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\IntegrationBundle\Manager\GenuineSyncScheduler;
use Oro\Bundle\IntegrationBundle\Provider\SyncProcessor;
use Oro\Component\Log\OutputLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

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
        if ($output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) {
            $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        }

        /** @var ChannelRepository $integrationRepository */
        /** @var SyncProcessor $processor */
        $connector = $input->getOption('connector');
        $integrationId = $input->getOption('integration');
        $connectorParameters = $this->getConnectorParameters($input);
        $integrationRepository = $this->getEntityManager()->getRepository(Integration::class);
        $logger = new OutputLogger($output);

        if ($integrationId) {
            $integration = $integrationRepository->getOrLoadById($integrationId);
            if (false == $integration) {
                throw new \LogicException(sprintf('Integration with given ID "%d" not found', $integrationId));
            }

            $integrations = [$integration];
        } else {
            $integrations = $integrationRepository->getConfiguredChannelsForSync(null, true);
        }

        /* @var Integration $integration */
        foreach ($integrations as $integration) {
            $logger->info(sprintf('Run sync for "%s" integration.', $integration->getName()));

            $this->getSyncScheduler()->schedule($integration->getId(), $connector, $connectorParameters);
        }

        $logger->info('Completed');
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
                        'Format for connector parameters is parameterKey=parameterValue. Got `%s`',
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
