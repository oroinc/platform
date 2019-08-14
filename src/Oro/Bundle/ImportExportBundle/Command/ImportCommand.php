<?php

namespace Oro\Bundle\ImportExportBundle\Command;

use Akeneo\Bundle\BatchBundle\Connector\ConnectorRegistry;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * This command provides possibility to import entities via command line
 */
class ImportCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'oro:import:file';

    /**
     * @var ProcessorRegistry
     */
    private $processorRegistry;

    /**
     * @var ConnectorRegistry
     */
    private $connectorRegistry;

    /**
     * @var MessageProducerInterface
     */
    private $messageProducer;

    /**
     * @var FileManager
     */
    private $fileManager;

    /**
     * @var UserManager
     */
    private $userManger;

    /**
     * @param ProcessorRegistry $processorRegistry
     * @param ConnectorRegistry $connectorRegistry
     * @param MessageProducerInterface $messageProducer
     * @param FileManager $fileManager
     * @param UserManager $userManger
     */
    public function __construct(
        ProcessorRegistry $processorRegistry,
        ConnectorRegistry $connectorRegistry,
        MessageProducerInterface $messageProducer,
        FileManager $fileManager,
        UserManager $userManger
    ) {
        $this->processorRegistry = $processorRegistry;
        $this->connectorRegistry = $connectorRegistry;
        $this->messageProducer = $messageProducer;
        $this->fileManager = $fileManager;
        $this->userManger = $userManger;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription(
                'Import data from specified file for specified entity. The import log is sent to the provided email.'
            )
            ->addArgument(
                'file',
                InputArgument::REQUIRED,
                'File name, to import CSV data from'
            )
            ->addOption(
                'jobName',
                null,
                InputOption::VALUE_REQUIRED,
                'Name of Import Job.'
            )
            ->addOption(
                'validation',
                null,
                InputOption::VALUE_NONE,
                'If adding this option then validation will be performed instead of import'
            )
            ->addOption(
                'processor',
                null,
                InputOption::VALUE_REQUIRED,
                'Name of the import processor.'
            )
            ->addOption(
                'email',
                null,
                InputOption::VALUE_REQUIRED,
                'Email to send the log after the import is completed'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (! is_file($sourceFile = $input->getArgument('file'))) {
            throw new \InvalidArgumentException(sprintf('File not found: %s', $sourceFile));
        }

        $email = $input->getOption('email');

        if ($email === null) {
            throw new \InvalidArgumentException('The --email option is required.');
        }

        $importOwner = $this->userManger->findUserByEmail((string) $email);
        if (!$importOwner instanceof User) {
            throw new \InvalidArgumentException(sprintf('Invalid email. There is no user with %s email!', $email));
        }

        $originFileName = basename($sourceFile);
        $fileName = FileManager::generateUniqueFileName(pathinfo($sourceFile, PATHINFO_EXTENSION));
        $this->fileManager->writeFileToStorage($sourceFile, $fileName);

        $processor = $input->getOption('validation') ?
            ProcessorRegistry::TYPE_IMPORT_VALIDATION :
            ProcessorRegistry::TYPE_IMPORT;

        $processorAlias = $this->handleProcessorName(
            $input,
            $output,
            $processor
        );

        $jobName = $this->handleJobName($input, $output, $processor);

        $this->messageProducer->send(Topics::PRE_IMPORT, [
            'fileName' => $fileName,
            'originFileName' => $originFileName,
            'userId' => $importOwner->getId(),
            'jobName' =>  $jobName,
            'processorAlias' => $processorAlias,
            'process' => $processor,
        ]);

        if ($email) {
            $output->writeln('Scheduled successfully. The result will be sent to the email');
        } else {
            $output->writeln('Scheduled successfully.');
        }
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param string          $type
     * @return string
     * @throws \InvalidArgumentException
     */
    private function handleProcessorName(
        InputInterface $input,
        OutputInterface $output,
        $type = ProcessorRegistry::TYPE_IMPORT
    ) {
        $option = 'processor';

        $label = ucwords(str_replace('-', ' ', $option));

        if ($input->getOption($option) &&
            $this->processorRegistry->hasProcessor($type, $input->getOption($option))
        ) {
            return $input->getOption($option);
        }

        $processors = $this->processorRegistry->getProcessorsByType($type);

        if (!$processors) {
            throw new \InvalidArgumentException('No configured processors');
        }

        $processorNames = array_keys($processors);
        if (!$input->getOption('no-interaction')) {
            $question = new ChoiceQuestion(sprintf('<question>Choose %s: </question>', $label), $processorNames);
            $selectedProcessorName = $this->getHelper('question')->ask($input, $output, $question);

            return $selectedProcessorName;
        }

        throw new \InvalidArgumentException(sprintf('Missing %s', $label));
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $type
     * @return string
     */
    private function handleJobName(
        InputInterface $input,
        OutputInterface $output,
        $type = ProcessorRegistry::TYPE_IMPORT
    ) {
        $jobName = $input->getOption('jobName');
        $jobNames = array_keys($this->connectorRegistry->getJobs($type)['oro_importexport']);
        if ($jobName && in_array($jobName, $jobNames)) {
            return $jobName;
        }
        if (!$input->getOption('no-interaction')) {
            $question = new ChoiceQuestion('<question>Choose Job: </question>', $jobNames);
            $selectedJobName = $this->getHelper('question')->ask($input, $output, $question);

            return $selectedJobName;
        }

        throw new \InvalidArgumentException('Missing "jobName" option.');
    }
}
