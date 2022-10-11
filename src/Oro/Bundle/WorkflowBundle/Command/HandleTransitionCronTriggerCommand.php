<?php
declare(strict_types=1);

namespace Oro\Bundle\WorkflowBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\WorkflowBundle\Async\Topic\WorkflowTransitionCronTriggerTopic;
use Oro\Bundle\WorkflowBundle\Async\TransitionTriggerMessage;
use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;
use Oro\Bundle\WorkflowBundle\Handler\TransitionCronTriggerHandler;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Triggers a workflow transition cron trigger.
 */
class HandleTransitionCronTriggerCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:workflow:handle-transition-cron-trigger';

    private ManagerRegistry $registry;
    private MessageProducerInterface $producer;
    private TransitionCronTriggerHandler $triggerHandler;

    public function __construct(
        ManagerRegistry $registry,
        MessageProducerInterface $producer,
        TransitionCronTriggerHandler $triggerHandler
    ) {
        parent::__construct();

        $this->registry = $registry;
        $this->producer = $producer;
        $this->triggerHandler = $triggerHandler;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function configure()
    {
        $this
            ->addOption(
                'id',
                null,
                InputOption::VALUE_REQUIRED,
                'Transition cron trigger ID'
            )
            ->setDescription('Triggers a workflow transition cron trigger.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command triggers
a specified workflow transition cron trigger.

  <info>php %command.full_name%</info>

HELP
            )
            ->addUsage('--id=<transition-cron-trigger-id>')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $triggerId = $input->getOption('id');
        if (!filter_var($triggerId, FILTER_VALIDATE_INT)) {
            $output->writeln('<error>No workflow transition cron trigger identifier defined</error>');
            return 1;
        }

        /** @var TransitionCronTrigger $trigger */
        $trigger = $this->getTransitionCronTriggerRepository()->find($triggerId);
        if (!$trigger) {
            $output->writeln('<error>Transition cron trigger not found</error>');
            return 1;
        }

        try {
            $start = microtime(true);
            $message = TransitionTriggerMessage::create($trigger);

            if ($trigger->isQueued()) {
                $this->producer->send(WorkflowTransitionCronTriggerTopic::getName(), $message->toArray());
            } else {
                $this->triggerHandler->process($trigger, $message);
            }

            $output->writeln(
                sprintf(
                    '<info>[%s] Transition cron trigger #%d of workflow "%s" successfully finished in %f s</info>',
                    (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'),
                    $triggerId,
                    $trigger->getWorkflowDefinition()->getName(),
                    microtime(true) - $start
                )
            );
        } catch (\Exception $e) {
            $output->writeln(
                sprintf(
                    '<error>[%s] Transition cron trigger #%s of workflow "%s" failed: %s</error>',
                    (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'),
                    $triggerId,
                    $trigger->getWorkflowDefinition()->getName(),
                    $e->getMessage()
                )
            );

            throw $e;
        }

        return 0;
    }

    /**
     * @return ObjectRepository
     */
    protected function getTransitionCronTriggerRepository()
    {
        return $this->registry->getManagerForClass(TransitionCronTrigger::class)
            ->getRepository(TransitionCronTrigger::class);
    }
}
