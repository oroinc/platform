<?php

namespace Oro\Bundle\WorkflowBundle\Command;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExecuteProcessJobCommand extends ContainerAwareCommand
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        if (!$this->entityManager) {
            $this->entityManager = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        }

        return $this->entityManager;
    }

    /**
     * @inheritdoc
     */
    public function configure()
    {
        $this->setName('oro:process:execute:job')
            ->setDescription('Execute process job with received identity')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'Identity of the process job that should be executed');
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $processJobId = $input->getOption('id');

        if (!$processJobId) {
            $output->writeln('<error>Process job id is required. Please enter --id=<process job identity></error>');
            return;
        }

        $processJobRepository = $this->getEntityManager()->getRepository('OroWorkflowBundle:ProcessJob');
        $processJob           = $processJobRepository->find($processJobId);

        if (!$this->processJobValidate($processJobId, $processJob, $output)) {
            return;
        }

        $this->getContainer()->get('oro_workflow.process.process_handler')->handleJob($processJob);
    }

    /**
     * @param $processJobId
     * @param ProcessJob $processJob
     * @param OutputInterface $output
     * @return bool
     */
    protected function processJobValidate($processJobId, $processJob, OutputInterface $output)
    {
        if (!$processJob) {
            $output->writeln(
                sprintf('<error>Process job with passed identity "%s" does not exist.</error>', $processJobId)
            );
            return false;
        } elseif ($processJob->getProcessTrigger()->getDefinition()->isEnabled()) {
            $output->writeln(
                sprintf('<error>Process job with passed identity "%s" already enabled.</error>', $processJobId)
            );
            return false;
        } else {
            return true;
        }
    }
}
