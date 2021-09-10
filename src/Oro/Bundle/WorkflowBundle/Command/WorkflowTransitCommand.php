<?php
declare(strict_types=1);

namespace Oro\Bundle\WorkflowBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\ForbiddenTransitionException;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Transitions a workflow item.
 */
class WorkflowTransitCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:workflow:transit';

    private ManagerRegistry $registry;
    private WorkflowManager $workflowManager;

    public function __construct(ManagerRegistry $managerRegistry, WorkflowManager $workflowManager)
    {
        parent::__construct();

        $this->registry = $managerRegistry;
        $this->workflowManager = $workflowManager;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function configure()
    {
        $this
            ->addOption('transition', null, InputOption::VALUE_REQUIRED, 'Transition name')
            ->addOption('workflow-item', null, InputOption::VALUE_REQUIRED, 'WorkflowItem ID')
            ->setDescription('Transitions a workflow item.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command executes a transition on a workflow item:

  <info>php %command.full_name% --transition=<transition> --workflow-item=<ID></info>

HELP
            )
            ->addUsage('--transition=<transition> --workflow-item=<ID>')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
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
            return $e->getCode() ?: 1;
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

        return 0;
    }
}
