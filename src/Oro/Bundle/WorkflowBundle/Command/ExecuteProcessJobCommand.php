<?php

namespace Oro\Bundle\WorkflowBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
            ->setDescription('Execute process job with specified identifier')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Identifier of the process job');
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

        $this->getContainer()->get('oro_workflow.process.process_handler')->handleJob($processJob);

        // remove process job and flush handled data
        $entityManager = $this->getRegistry()->getManagerForClass('OroWorkflowBundle:ProcessJob');
        $entityManager->remove($processJob);
        $entityManager->flush();
    }
}
