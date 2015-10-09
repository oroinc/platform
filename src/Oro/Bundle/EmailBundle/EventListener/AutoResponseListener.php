<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\ORM\Event\PostFlushEventArgs;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\EmailBundle\Command\AutoResponseCommand;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Manager\AutoResponseManager;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;

class AutoResponseListener extends MailboxEmailListener
{
    /** @var ServiceLink */
    private $autoResponseManagerLink;

    /** @var EmailUser[] */
    protected $emailUsers = [];

    /**
     * @param ServiceLink $autoResponseManagerLink
     */
    public function __construct(ServiceLink $autoResponseManagerLink)
    {
        $this->autoResponseManagerLink = $autoResponseManagerLink;
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        $emailIds = $this->popEmailIds();
        if (!$emailIds) {
            return;
        }

        $jobArgs = array_map(function ($id) {
            return sprintf('--%s=%s', AutoResponseCommand::OPTION_ID, $id);
        }, $emailIds);
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
        $emailIds = array_map(
            function (EmailBody $emailBody) {
                return $emailBody->getEmail()->getId();
            },
            array_filter(
                $this->emailBodies,
                function (EmailBody $emailBody) {
                    return $this->getAutoResponseManager()->hasAutoResponses($emailBody->getEmail());
                }
            )
        );
        $this->emailBodies = [];

        return array_values($emailIds);
    }


    /**
     * @return AutoResponseManager
     */
    protected function getAutoResponseManager()
    {
        return $this->autoResponseManagerLink->getService();
    }
}
