<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Mailbox\MailboxProcessStorage;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Compiler\ServiceLinkPass;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Bundle\WorkflowBundle\Model\ProcessHandler;

class MailboxProcessTriggerListener
{
    /** @var ProcessHandler */
    protected $handler;
    /** @var EmailBody[] */
    protected $emailBodies;
    /** @var ServiceLink */
    protected $processStorage;
    /** @var ServiceLink */
    protected $doctrine;

    public function __construct(
        ProcessHandler $handler,
        ServiceLink $processStorage,
        ServiceLink $doctrine
    ) {
        $this->handler = $handler;
        $this->processStorage = $processStorage;
        $this->doctrine = $doctrine;
    }

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $oid => $entity) {
            if ($entity instanceof EmailUser) {
                /*
                 * If EmailUser is being persisted and body of its email is already synced.
                 * Process will be triggered provided that is has not yet been assigned to any mailbox.
                 */
                $email = $entity->getEmail();
                $mailboxEmailUsers = $email->getEmailUsers()->filter(
                    function (EmailUser $emailUser) {
                        return $emailUser->getId() && $emailUser->getMailboxOwner();
                    }
                );

                $emailBody = $email->getEmailBody();
                if ($mailboxEmailUsers->isEmpty() && $emailBody && $emailBody->getId()) {
                    $this->emailBodies[spl_object_hash($emailBody)] = $emailBody;
                }
            } elseif ($entity instanceof EmailBody) {
                /*
                 * If new email body is created ...
                 * Schedule it for processing.
                 */
                $this->emailBodies[$oid] = $entity;
            }
        }
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        if (empty($this->emailBodies)) {
            return;
        }

        foreach ($this->emailBodies as $emailBody) {
            $this->scheduleProcess($emailBody);
        }

        $this->emailBodies = [];
    }

    /**
     * Schedules EmailBody for processing.
     *
     * @param EmailBody $emailBody
     */
    protected function scheduleProcess(EmailBody $emailBody)
    {
        /*
         * Retrieve all process definitions to trigger
         */
        $definitions = $this->processStorage->getService()->getProcessDefinitionNames();
        $definitions = $this->getDefinitionRepository()->findBy(['name' => $definitions]);

        /*
         * Trigger process definitions with provided data
         */
        foreach ($definitions as $definition) {
            $trigger = new ProcessTrigger();
            $trigger->setDefinition($definition);

            $data = new ProcessData();
            $data->set('data', $emailBody);

            $this->handler->handleTrigger($trigger, $data);
        }
    }

    /**
     * @return EntityRepository
     */
    protected function getDefinitionRepository()
    {
        return $this->doctrine->getService()->getRepository('OroWorkflowBundle:ProcessDefinition');
    }
}
