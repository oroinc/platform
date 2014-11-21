<?php

namespace Oro\Bundle\IntegrationBundle\Command;

use JMS\JobQueueBundle\Entity\Job;

use Doctrine\ORM\QueryBuilder;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Oro\Component\Log\OutputLogger;

use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;

/**
 * Class ReverseSyncCommand
 * Console command implementation
 *
 * @package Oro\Bundle\IntegrationBundle\Command
 */
class ReverseSyncCommand extends ContainerAwareCommand
{
    const SYNC_PROCESSOR       = 'oro_integration.reverse_sync.processor';
    const COMMAND_NAME         = 'oro:integration:reverse:sync';
    const INTEGRATION_ARG_NAME = 'integration';
    const CONNECTOR_ARG_NAME   = 'connector';
    const PARAMETERS_ARG_NAME  = 'params';
    const STATUS_SUCCESS       = 0;
    const STATUS_FAILED        = 255;

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName(static::COMMAND_NAME)
            ->addOption(self::INTEGRATION_ARG_NAME, 'i', InputOption::VALUE_REQUIRED, 'Integration id')
            ->addOption(self::CONNECTOR_ARG_NAME, 'con', InputOption::VALUE_REQUIRED, 'Connector type')
            ->addOption(self::PARAMETERS_ARG_NAME, null, InputOption::VALUE_REQUIRED, 'Parameters');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $integrationId   = $input->getOption(self::INTEGRATION_ARG_NAME);
        $connectorType   = $input->getOption(self::CONNECTOR_ARG_NAME);
        $params          = $input->getOption(self::PARAMETERS_ARG_NAME);
        $convertedParams = unserialize(stripslashes($params));
        $logger          = new OutputLogger($output);
        $processor       = $this->getService(self::SYNC_PROCESSOR);
        $exitCode        = self::STATUS_SUCCESS;
        /** @var ChannelRepository $repository */
        $repository = $this->getService('doctrine.orm.entity_manager')
            ->getRepository('OroIntegrationBundle:Channel');

        if (!is_array($convertedParams)) {
            throw new \InvalidArgumentException('Parameters option must be serialized string.');
        }

        $processor->getLoggerStrategy()->setLogger($logger);

        $this->getContainer()
            ->get('doctrine.orm.entity_manager')
            ->getConnection()
            ->getConfiguration()
            ->setSQLLogger(null);

        if ($this->isJobRunning($integrationId, $connectorType, $params)) {
            $logger->warning('Job already running. Terminating....');

            return self::STATUS_SUCCESS;
        }

        try {
            $integration = $repository->getOrLoadById($integrationId);
            if (empty($integration)) {
                throw new \InvalidArgumentException('Integration with given ID not found');
            }

            $logger->notice(
                sprintf(
                    'Run sync for "%s" integration and "%s" connector.',
                    $integration->getName(),
                    $connectorType
                )
            );

            $processor->process($integration, $connectorType, $convertedParams);
        } catch (\Exception $e) {
            $logger->critical($e->getMessage(), ['exception' => $e]);

            $exitCode = self::STATUS_FAILED;
        }

        $logger->notice('Completed');

        return $exitCode;
    }

    /**
     * Get service from DI container by id
     *
     * @param string $id
     *
     * @return object
     */
    protected function getService($id)
    {
        return $this->getContainer()->get($id);
    }

    /**
     * @param int    $integrationId
     * @param string $connectorTypeType
     * @param string $params
     *
     * @return bool
     */
    protected function isJobRunning($integrationId, $connectorTypeType, $params)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->getService('doctrine.orm.entity_manager')
            ->getRepository('JMSJobQueueBundle:Job')
            ->createQueryBuilder('j');

        $query = $qb->select('count(j.id)')
            ->andWhere('j.command=:commandName')
            ->andWhere('j.state=:stateName')
            ->setParameter('commandName', $this->getName())
            ->setParameter('stateName', Job::STATE_RUNNING)
            ->andWhere(
                $qb->expr()->eq('j.args', ':args')
            )
            ->setParameter(
                'args',
                '["--integration=' . $integrationId . '",' .
                ' "--connector=' . $connectorTypeType . '",' .
                ' "--params=\'' . $params . '\'"]'
            );

        return (int)$query->getQuery()->getSingleScalarResult() > 0;
    }
}
