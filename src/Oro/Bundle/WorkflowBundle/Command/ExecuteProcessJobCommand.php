<?php

namespace Oro\Bundle\WorkflowBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;

class ExecuteProcessJobCommand extends ContainerAwareCommand
{
    const NAME = 'oro:process:execute:job';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

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
        $processHandler = $this->getContainer()->get('oro_workflow.process.process_handler');

        /** @var ProcessJob $processJob */
        foreach ($processJobs as $processJob) {
            $processId = $processJob->getId();
            $entityManager->beginTransaction();
            try {
                $processHandler->handleJob($processJob);
                $this->finishJob($entityManager, $processJob);

                $output->writeln(sprintf('<info>Process %s successfully finished</info>', $processId));
            } catch (\Exception $e) {
                $entityManager->rollback();

                $output->writeln(sprintf('<error>Process %s failed: %s</error>', $processId, $e->getMessage()));
            }
        }
    }

    /**
     * @param EntityManager $entityManager
     * @param ProcessJob $processJob
     */
    protected function finishJob(EntityManager $entityManager, ProcessJob $processJob)
    {
        $entityManager->remove($processJob);
        $entityManager->flush();
        $entityManager->commit();
    }
}
