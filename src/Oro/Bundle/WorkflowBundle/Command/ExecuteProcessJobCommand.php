<?php

namespace Oro\Bundle\WorkflowBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Model\ProcessHandler;

class ExecuteProcessJobCommand extends ContainerAwareCommand
{
    const NAME = 'oro:process:execute:job';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ProcessHandler
     */
    protected $processHandler;

    /**
     * @return ManagerRegistry
     */
    protected function getRegistry()
    {
        if (!$this->registry) {
            $this->registry = $this->getContainer()->get('doctrine');
        }

        return $this->registry;
    }

    /**
     * @return ProcessHandler
     */
    protected function getProcessHandler()
    {
        if (!$this->processHandler) {
            $this->processHandler = $this->getContainer()->get('oro_workflow.process.process_handler');
        }

        return $this->processHandler;
    }

    /**
     * @inheritdoc
     */
    public function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Execute process job with specified identifiers')
            ->addOption(
                'id',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Identifiers of the process jobs'
            );
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $registry      = $this->getRegistry();
        $processJobIds = $input->getOption('id');
        $processJobs   = $registry->getRepository('OroWorkflowBundle:ProcessJob')->findByIds($processJobIds);

        if (!$processJobs) {
            $output->writeln('<error>Process jobs with passed identifiers do not exist</error>');
            return;
        }

        /** @var EntityManager $entityManager */
        $entityManager  = $registry->getManagerForClass('OroWorkflowBundle:ProcessJob');
        $processHandler = $this->getProcessHandler();

        /** @var ProcessJob $processJob */
        foreach ($processJobs as $processJob) {
            $processId = $processJob->getId();
            $entityManager->beginTransaction();
            try {
                $processHandler->handleJob($processJob);
                $entityManager->remove($processJob);
                $entityManager->flush();
                $processHandler->finishJob($processJob);
                $entityManager->commit();

                $output->writeln(sprintf('<info>Process %s successfully finished</info>', $processId));
            } catch (\Exception $e) {
                $processHandler->finishJob($processJob);
                $entityManager->rollback();

                $output->writeln(sprintf('<error>Process %s failed: %s</error>', $processId, $e->getMessage()));
            }
        }
    }
}
