<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Bundle\WorkflowBundle\Model\ProcessHandler;

class MailboxProcessTriggerListener extends MailboxEmailListener
{
    /** @var ProcessHandler */
    protected $handler;

    /** @var ServiceLink */
    protected $processStorage;

    /** @var Registry */
    protected $doctrine;

    /**
     * @param ProcessHandler $handler
     * @param ServiceLink    $processStorage
     * @param Registry       $doctrine
     */
    public function __construct(
        ProcessHandler $handler,
        ServiceLink $processStorage,
        Registry $doctrine
    ) {
        $this->handler = $handler;
        $this->processStorage = $processStorage;
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc} In addition it filters out emails which are part of thread
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        parent::onFlush($args);
        $this->emailBodies = array_filter(
            $this->emailBodies,
            function (EmailBody $body) {
                return $body->getEmail() && !$body->getEmail()->getThread();
            }
        );
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
            //id must be unique otherwise in cache will be saved and runned first definition with id = null
            $trigger->setId($definition->getName());
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
        return $this->doctrine->getRepository('OroWorkflowBundle:ProcessDefinition');
    }
}
