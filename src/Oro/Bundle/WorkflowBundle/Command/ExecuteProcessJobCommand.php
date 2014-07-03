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
            $output->writeln(
                sprintf('<error>Process jobs with passed identities does not exist.</error>')
            );
            return;
        }

        $entityManager = $registry->getManagerForClass('OroWorkflowBundle:ProcessJob');

        /** @var ProcessJob $processJob */
        foreach ($processJobs as $processJob) {
            $this->getContainer()->get('oro_workflow.process.process_handler')->handleJob($processJob);
            $output->writeln(sprintf(
                '  <comment>></comment> <info>Successfully done process: %s</info>',
                $processJob->getId()
            ));
            $entityManager->remove($processJob);
        }

        // remove process job and flush handled data
        $entityManager->flush();
    }
}
