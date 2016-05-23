<?php

namespace Oro\Bundle\IntegrationBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

/**
 * TODO CRM-5839 remove it
 *
 * @deprecated
 */
abstract class AbstractSyncCronCommand extends ContainerAwareCommand implements CronCommandInterface
{
    /**
     * @deprecated
     *
     * Check is job running (from previous schedule)
     *
     * @param null|int $integrationId
     *
     * @return bool
     */
    protected function isJobRunning($integrationId)
    {
        /** @var ManagerRegistry $managerRegistry */
        $managerRegistry = $this->getService('doctrine');
        $running = $managerRegistry->getRepository('OroIntegrationBundle:Channel')
            ->getRunningSyncJobsCount($this->getName(), $integrationId);

        return $running > 1;
    }

    /**
     * @deprecated
     *
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
}
