<?php

namespace Oro\Bundle\WorkflowBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\ForbiddenTransitionException;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Execute transition of workflow
 */
class WorkflowTransitCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:workflow:transit';

    /** @var ManagerRegistry */
    private $registry;

    /** @var WorkflowManager */
    private $workflowManager;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param WorkflowManager $workflowManager
     */
    public function __construct(ManagerRegistry $managerRegistry, WorkflowManager $workflowManager)
    {
        parent::__construct();

        $this->registry = $managerRegistry;
        $this->workflowManager = $workflowManager;
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setDescription('Execute transition of workflow')
            ->addOption(
                'workflow-item',
                null,
                InputOption::VALUE_REQUIRED,
                'Identifier of WorkflowItem'
            )
            ->addOption(
                'transition',
                null,
                InputOption::VALUE_REQUIRED,
                'Name of Transition'
            );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $workflowItemId = $input->getOption('workflow-item');
        $transitionName = $input->getOption('transition');

        try {
            if (!filter_var($workflowItemId, FILTER_VALIDATE_INT)) {
                throw new \RuntimeException('No Workflow Item identifier defined');
            }

            if (!$transitionName) {
                throw new \RuntimeException('No Transition name defined');
            }

            /** @var WorkflowItem $workflowItem */
            $workflowItem = $this->registry->getRepository(WorkflowItem::class)->find($workflowItemId);
            if (!$workflowItem) {
                throw new \RuntimeException('Workflow Item not found');
            }
        } catch (\RuntimeException $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            throw $e;
        }

        $output->writeln('<info>Start transition...</info>');
        try {
            $this->workflowManager->transit($workflowItem, $transitionName);
            $output->writeln(
                sprintf(
                    '<info>Workflow Item #%s transition #%s successfully finished</info>',
                    $workflowItemId,
                    $transitionName
                )
            );
        } catch (ForbiddenTransitionException $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

            // do not throw exception for ForbiddenTransitionException (see BAP-10872)
            return;
        } catch (\Exception $e) {
            $output->writeln(
                sprintf(
                    '<error>[%s] Transition #%s is failed: %s</error>',
                    (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'),
                    $transitionName,
                    $e->getMessage()
                )
            );

            throw $e;
        }
    }
}
