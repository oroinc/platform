<?php

namespace Oro\Bundle\WorkflowBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\CronBundle\Command\Logger\OutputLogger;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExecuteProcessJobCommand extends ContainerAwareCommand
{
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
        $this->setName('oro:process:execute:job')
            ->setDescription('Execute process job with received identity')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Identity of the process job that should be executed');
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $processJobId         = $input->getOption('id');
        $processJobRepository = $this->getRegistry()->getRepository('OroWorkflowBundle:ProcessJob');
        $processJob           = $processJobRepository->find($processJobId);

        if (!$processJob) {
            $output->writeln(
                sprintf('<error>Process job with passed identity "%s" does not exist.</error>', $processJobId)
            );
            return;
        }

        try {
            $this->getContainer()->get('oro_workflow.process.process_handler')->handleJob($processJob);
            $entityManager = $this->getRegistry()->getManagerForClass('OroWorkflowBundle:ProcessJob');
            $entityManager->remove($processJob);
            $entityManager->flush();
        } catch (\Exception $e) {
            $logger = new OutputLogger($output);
            $logger->critical($e->getMessage(), ['exception' => $e]);
        }
    }
}
