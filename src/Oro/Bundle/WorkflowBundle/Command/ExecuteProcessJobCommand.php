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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $registry      = $this->getRegistry();
        $processJobIds = $input->getOption('id');

        if (!$processJobIds) {
            $output->writeln('<error>No process identifiers defined</error>');
            return;
        }

        $processHandler = $this->getProcessHandler();
        $firstException = null;

        /** @var ProcessJob $processJob */
        foreach ($processJobIds as $processJobId) {
            // make sure that every process will be handled with clear entity manager
            $registry->resetManager();

            $processJob = $registry->getRepository('OroWorkflowBundle:ProcessJob')->find($processJobId);
            if (!$processJob) {
                $output->writeln(sprintf('<error>Process job %s does not exist</error>', $processJobId));
                continue;
            }

            /** @var EntityManager $entityManager */
            $entityManager = $registry->getManager();
            $entityManager->beginTransaction();

            try {
                $processDefinition = $processJob->getProcessTrigger()->getDefinition();

                $start = microtime(true);
                $output->writeln(
                    sprintf(
                        '<info>[%s] Executing process job #%d %s</info>',
                        (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'),
                        $processJobId,
                        $processDefinition->getName()
                    )
                );

                $processHandler->handleJob($processJob);
                $entityManager->remove($processJob);
                $entityManager->flush();

                $processHandler->finishJob($processJob);
                $entityManager->clear();
                $entityManager->commit();

                $output->writeln(
                    sprintf(
                        '<info>[%s] Process job #%d successfully finished in %f s</info>',
                        (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'),
                        $processJobId,
                        microtime(true) - $start
                    )
                );
            } catch (\Exception $e) {
                $processHandler->finishJob($processJob);
                $entityManager->clear();
                $entityManager->rollback();

                // save first exception
                if (!$firstException) {
                    $firstException = $e;
                }

                $output->writeln(
                    sprintf(
                        '<error>[%s] Process job #%s failed: %s</error>',
                        (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'),
                        $processJobId,
                        $e->getMessage()
                    )
                );
            }
        }

        // throw first exception
        if ($firstException) {
            throw $firstException;
        }
    }
}
