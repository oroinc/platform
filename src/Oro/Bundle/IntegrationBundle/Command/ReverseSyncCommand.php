<?php

namespace Oro\Bundle\IntegrationBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\IntegrationBundle\Manager\SyncScheduler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ReverseSyncCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('oro:integration:reverse:sync')
            ->addOption('integration', 'i', InputOption::VALUE_REQUIRED, 'Integration id')
            ->addOption('connector', 'con', InputOption::VALUE_REQUIRED, 'Connector type')
            ->addArgument(
                'connector-parameters',
                InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                'Additional connector parameters array. Format - parameterKey=parameterValue',
                []
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) {
            $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        }

        $integrationId   = $input->getOption('integration');
        $connector   = $input->getOption('connector');
        if (! $connector) {
            throw new \InvalidArgumentException('Connector must be set');
        }
        $connectorParameters = $this->getConnectorParameters($input);
        /** @var ChannelRepository $integrationRepository */
        $integrationRepository = $this->getEntityManager()->getRepository(Integration::class);

        $integration = $integrationRepository->getOrLoadById($integrationId);
        if (empty($integration)) {
            throw new \InvalidArgumentException(sprintf('Integration with id "%s" is not found', $integrationId));
        }

        $output->writeln(sprintf(
            'Schedule reverse sync for "%s" integration and "%s" connector.',
            $integration->getName(),
            $connector
        ));

        $this->getReverseSyncScheduler()->schedule($integration->getId(), $connector, $connectorParameters);
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
                    throw new \LogicException('Connector parameters should be in "parameterKey=parameterValue" format');
                }
                $result[$parameterConfigArray[0]] = $parameterConfigArray[1];
            }
        }

        return $result;
    }

    /**
     * @return EntityManagerInterface
     */
    private function getEntityManager()
    {
        return $this->container->get('doctrine.orm.entity_manager');
    }

    /**
     * @return SyncScheduler
     */
    private function getReverseSyncScheduler()
    {
        return $this->container->get('oro_integration.sync_scheduler');
    }
}
