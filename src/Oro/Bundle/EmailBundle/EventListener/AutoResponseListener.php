<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\EmailBundle\Command\AutoResponseCommand;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Manager\AutoResponseManager;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;

class AutoResponseListener
{
    /** @var ServiceLink */
    private $autoResponseManagerLink;

    /** @var EmailBody[] */
    protected $emailBodies = [];

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
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        $emails = [];
        foreach ($uow->getScheduledEntityInsertions() as $oid => $entity) {
            if ($entity instanceof EmailUser) {
                /**
                 * Collect already flushed emails with bodies with later check
                 * if there is new binding to mailbox
                 * (email was sent from the system and now mailbox is synchonized)
                 */
                $email = $entity->getEmail();
                if ($email && $email->getId() && $email->getEmailBody() && $entity->getMailboxOwner()) {
                    $emails[$email->getId()] = $email;
                }
            } elseif ($entity instanceof EmailBody) {
                $this->emailBodies[$oid] = $entity;
            }
        }

        if ($emails) {
            $emailsToProccess = $this->filterEmailsWithNewlyBoundMailboxes($em, $emails);
            foreach ($emailsToProccess as $email) {
                $this->emailBodies[spl_object_hash($email->getEmailBody())] = $email->getEmailBody();
            }
        }
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
     * @param EntityManager $em
     * @param Email[] $emails
     *
     * @return Email[]
     */
    protected function filterEmailsWithNewlyBoundMailboxes(EntityManager $em, array $emails)
    {
        $qb = $em->getRepository('OroEmailBundle:EmailUser')->createQueryBuilder('eu');
        $emailIdsWithAlreadyBoundMailboxesResult = $qb
            ->select('e.id')
            ->andWhere($qb->expr()->in('e.id', ':ids'))
            ->join('eu.mailboxOwner', 'mo')
            ->join('eu.email', 'e')
            ->setParameter('ids', array_keys($emails))
            ->getQuery()
            ->getResult();

        return array_diff_key(
            $emails,
            array_flip(array_map('current', $emailIdsWithAlreadyBoundMailboxesResult))
        );
    }

    /**
     * @return AutoResponseManager
     */
    protected function getAutoResponseManager()
    {
        return $this->autoResponseManagerLink->getService();
    }
}
