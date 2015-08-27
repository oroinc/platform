<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Event\PostFlushEventArgs;

use Oro\Bundle\ActivityListBundle\Entity\Manager\CollectListManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Bundle\WorkflowBundle\Model\ProcessHandler;
use Oro\Bundle\WorkflowBundle\Event\ProcessHandleEvent;

class MailboxProcessTriggerListener extends MailboxEmailListener
{
    /** @var ProcessHandler */
    protected $handler;

    /** @var ServiceLink */
    protected $processStorage;

    /** @var Registry */
    protected $doctrine;

    /** @var  CollectListManager */
    protected $collectManager;

    /**
     * @param ProcessHandler $handler
     * @param ServiceLink    $processStorage
     * @param Registry       $doctrine
     */
    public function __construct(
        ProcessHandler $handler,
        ServiceLink $processStorage,
        Registry $doctrine,
        CollectListManager $collectManager
    ) {
        $this->handler = $handler;
        $this->processStorage = $processStorage;
        $this->doctrine = $doctrine;
        $this->collectManager = $collectManager;
    }

    /**
     * Processes email bodies using processes provided in MailboxProcessProviders.
     * Processes are triggered using this listener instead of normal triggers.
     * Processes are triggered for new email bodies and email bodies of emails newly bound to some mailbox.
     *
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        if (empty($this->emailBodies)) {
            return;
        }
        $emailBodies = $this->emailBodies;
        $this->emailBodies = [];

        foreach ($emailBodies as $emailBody) {
            $this->scheduleProcess($emailBody);
        }

        $this->doctrine->getManager()->flush();
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

    public function addOwner(ProcessHandleEvent $event)
    {
        $definition = $event->getProcessTrigger()->getDefinition();
        $definitions = $this->processStorage->getService()->getProcessDefinitionNames();
        if (in_array($definition->getName(), $definitions)) {
            /**
             * @var Email $mail
             */
            $mail = $event->getProcessData()->get('email');
            $this->collectManager->processFillOwners($mail->getEmailUsers(), $this->doctrine->getEntityManager());
        }
    }

    /**
     * @return EntityRepository
     */
    protected function getDefinitionRepository()
    {
        return $this->doctrine->getRepository('OroWorkflowBundle:ProcessDefinition');
    }
}
