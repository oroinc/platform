<?php

namespace Oro\Bundle\WorkflowBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Bundle\WorkflowBundle\Model\ProcessHandler;

class ExecuteProcessTriggerCommand extends ContainerAwareCommand
{
    const NAME = 'oro:process:trigger:execute';

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
                InputOption::VALUE_REQUIRED,
                'Identifier of the process triggers'
            )->addOption(
                'name',
                null,
                InputOption::VALUE_OPTIONAL,
                'Name of the process (optional)'
            );
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $triggerId = $input->getOption('id');

        if (!is_numeric($triggerId)) {
            $output->writeln('<error>No process trigger identifier defined</error>');
            return;
        }

        $processHandler = $this->getProcessHandler();
        $processTrigger = $this->getRegistry()
            ->getManagerForClass('OroWorkflowBundle:ProcessTrigger')
            ->getRepository('OroWorkflowBundle:ProcessTrigger')
            ->find($triggerId);

        if (!$processTrigger) {
            $output->writeln('<error>Process trigger not found</error>');
            return;
        }
        $processDefinition = $processTrigger->getDefinition();
        $entityClass = $processDefinition->getRelatedEntity();
        $processData = new ProcessData();

        /** @var EntityManager $entityManager */
        $entityManager = $this->getRegistry()->getManager();
        $entityManager->beginTransaction();
        try {
            $processData->set('data', new $entityClass);
            $start = microtime(true);
            $output->writeln(
                sprintf(
                    '<info>[%s] Executing process trigger #%d "%s" (%s)</info>',
                    (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'),
                    $triggerId,
                    $processDefinition->getLabel(),
                    $processDefinition->getName()
                )
            );

            $processHandler->handleTrigger($processTrigger, $processData);
            $entityManager->clear();
            $entityManager->commit();
            $output->writeln(
                sprintf(
                    '<info>[%s] Process trigger #%d execution %s successfully finished in %f s</info>',
                    (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'),
                    $triggerId,
                    $processDefinition->getName(),
                    microtime(true) - $start
                )
            );
        } catch (\Exception $e) {
            $entityManager->clear();
            $entityManager->rollback();

            $output->writeln(
                sprintf(
                    '<error>[%s] Process trigger #%s execution failed: %s</error>',
                    (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'),
                    $triggerId,
                    $e->getMessage()
                )
            );
        }
    }
}
