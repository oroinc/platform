<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Mailbox\MailboxProcessStorage;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Bundle\WorkflowBundle\Model\ProcessHandler;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Used to process email bodies using processes provided in mailbox process providers.
 */
class MailboxProcessTriggerListener extends MailboxEmailListener implements
    FeatureToggleableInterface,
    ServiceSubscriberInterface
{
    use FeatureCheckerHolderTrait;

    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_email.mailbox.process_storage' => MailboxProcessStorage::class,
            'oro_workflow.process.process_handler' => ProcessHandler::class,
            LoggerInterface::class
        ];
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
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        if (empty($this->emailBodies) || !$this->isFeaturesEnabled()) {
            return;
        }

        $emailBodies = $this->emailBodies;
        $this->emailBodies = [];

        $em = $args->getEntityManager();
        $processRepository = $em->getRepository(ProcessDefinition::class);
        $processStorage = $this->container->get('oro_email.mailbox.process_storage');
        $handler = $this->container->get('oro_workflow.process.process_handler');

        foreach ($emailBodies as $emailBody) {
            $this->scheduleProcess($emailBody, $processRepository, $processStorage, $handler);
        }

        $em->flush();
    }

    /**
     * Schedules EmailBody for processing.
     */
    protected function scheduleProcess(
        EmailBody $emailBody,
        EntityRepository $processRepository,
        MailboxProcessStorage $processStorage,
        ProcessHandler $handler
    ) {
        /*
         * Retrieve all process definitions to trigger
         */
        $definitions = $processStorage->getProcessDefinitionNames();
        /** @var ProcessDefinition[] $definitions */
        $definitions = $processRepository->findBy(['name' => $definitions]);

        /*
         * Trigger process definitions with provided data
         */
        foreach ($definitions as $definition) {
            try {
                $trigger = new ProcessTrigger();
                //id must be unique otherwise in cache will be saved and runned first definition with id = null
                $trigger->setId($definition->getName());
                $trigger->setDefinition($definition);
                $trigger->setEvent(ProcessTrigger::EVENT_CREATE);

                $data = new ProcessData();
                $data->set('data', $emailBody);

                $handler->handleTrigger($trigger, $data);
            } catch (\Exception $ex) {
                /** @var LoggerInterface $logger */
                $logger = $this->container->get(LoggerInterface::class);
                $logger->warning(
                    sprintf(
                        'Process failed and skipped: %s. Error: %s.',
                        $definition->getName(),
                        $ex->getMessage()
                    ),
                    ['exception' => $ex]
                );
            }
        }
    }
}
