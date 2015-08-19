<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\EmailBundle\Command\AutoResponseCommand;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\Repository\AutoResponseRuleRepository;

class AutoResponseListener
{
    /** @var EmailBody[] */
    protected $emailBodies = [];

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        /* @var $autoResponseRuleRepository AutoResponseRuleRepository */
        $autoResponseRuleRepository = $em->getRepository('OroEmailBundle:AutoResponseRule');
        if (!$autoResponseRuleRepository->rulesExists()) {
            return;
        }

        $uow = $em->getUnitOfWork();
        $this->emailBodies = array_merge(
            $this->emailBodies,
            array_filter($uow->getScheduledEntityInsertions(), function ($entity) {
                return $entity instanceof EmailBody;
            })
        );
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        if (!$this->emailBodies) {
            return;
        }

        $jobArgs = array_map(function ($id) {
            return sprintf('--%s=%s', AutoResponseCommand::OPTION_ID, $id);
        }, $this->popEmailIds());
        $job = new Job(AutoResponseCommand::NAME, $jobArgs);

        $em = $args->getEntityManager();
        $em->persist($job);
        $em->flush();
    }

    /**
     * @return array
     */
    protected function popEmailIds()
    {
        $emailIds = array_map(function (EmailBody $emailBody) {
            return $emailBody->getEmail()->getId();
        }, $this->emailBodies);
        $this->emailBodies = [];

        return array_values($emailIds);
    }
}
