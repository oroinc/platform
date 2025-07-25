<?php

declare(strict_types=1);

namespace Oro\Bundle\ImportExportBundle\Command;

use Oro\Bundle\BatchBundle\Connector\ConnectorRegistry;
use Oro\Bundle\ImportExportBundle\Async\Topic\PreImportTopic;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Imports data from a file.
 */
#[AsCommand(
    name: 'oro:import:file',
    description: 'Imports data from a file.'
)]
class ImportCommand extends Command
{
    private ProcessorRegistry $processorRegistry;
    private ConnectorRegistry $connectorRegistry;
    private MessageProducerInterface $messageProducer;
    private FileManager $fileManager;
    private UserManager $userManger;

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

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    protected function configure()
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'File name')
            ->addOption('jobName', null, InputOption::VALUE_REQUIRED, 'Import job name')
            ->addOption('processor', null, InputOption::VALUE_REQUIRED, 'Import processor name')
            ->addOption('validation', null, InputOption::VALUE_NONE, 'Only validate data instead of import')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Email to send the import log to')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command imports data from a file.
This command only schedules the job by adding a message to the message queue, so ensure
that the message consumer processes (<info>oro:message-queue:consume</info>) are running
for data to be imported.

  <info>php %command.full_name% --email=<email> <file></info>

The <info>--email</info> option should be the email address of the owner of the new records
(unless a different owner is specified in the data file). This user will also
receive the import log after the import is finished:

The <info>--jobName</info> and <info>--processor</info> options should be used in non-interactive mode
to provide names of the job and import processor that can handle data import:

  <info>php %command.full_name% --email=<email> --jobName=<job> --processor=<processor> <file></info>

In interactive mode the job and import processor can be selected from a list.

The <info>--validation</info> option can be used to validate the data instead of importing it:

  <info>php %command.full_name% --validate --email=<email> --jobName=<job> --processor=<processor> <file></info>

HELP
            )
            ->addUsage('--email=<email> --jobName=<job> --processor=<processor> <file>')
            ->addUsage('--validation --email=<email> --jobName=<job> --processor=<processor> <file>')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sourceFile = $input->getArgument('file');
        if (!is_file($sourceFile)) {
            throw new RuntimeException(sprintf('File not found: %s', $sourceFile));
        }

        $userId = $this->handleEmail($input);
        $processorType = $input->getOption('validation')
            ? ProcessorRegistry::TYPE_IMPORT_VALIDATION
            : ProcessorRegistry::TYPE_IMPORT;
        $processorAlias = $this->handleProcessorName($input, $output, $processorType);
        $jobName = $this->handleJobName($input, $output, $processorType);

        $originFileName = basename($sourceFile);
        $fileName = FileManager::generateUniqueFileName(pathinfo($sourceFile, PATHINFO_EXTENSION));
        $this->fileManager->writeFileToStorage($sourceFile, $fileName);

        $this->messageProducer->send(
            PreImportTopic::getName(),
            [
                'fileName' => $fileName,
                'originFileName' => $originFileName,
                'userId' => $userId,
                'jobName' =>  $jobName,
                'processorAlias' => $processorAlias,
                'process' => $processorType
            ]
        );

        $output->writeln('Scheduled successfully. The result will be sent to the email');

        return Command::SUCCESS;
    }

    private function handleProcessorName(InputInterface $input, OutputInterface $output, string $processorType): string
    {
        $processor = $input->getOption('processor');
        if ($processor && $this->processorRegistry->hasProcessor($processorType, $processor)) {
            return $processor;
        }

        $processors = $this->processorRegistry->getProcessorsByType($processorType);
        if (!$processors) {
            throw new RuntimeException('No configured processors');
        }

        $processorNames = array_keys($processors);
        if (!$input->getOption('no-interaction')) {
            return $this->getHelper('question')->ask(
                $input,
                $output,
                new ChoiceQuestion('<question>Choose Processor: </question>', $processorNames)
            );
        }

        throw new RuntimeException('Missing "processor" option.');
    }

    private function handleJobName(InputInterface $input, OutputInterface $output, string $processorType): string
    {
        $jobName = $input->getOption('jobName');
        $jobNames = array_keys($this->connectorRegistry->getJobs($processorType)['oro_importexport']);
        if ($jobName && \in_array($jobName, $jobNames, true)) {
            return $jobName;
        }

        if (!$input->getOption('no-interaction')) {
            return $this->getHelper('question')->ask(
                $input,
                $output,
                new ChoiceQuestion('<question>Choose Job: </question>', $jobNames)
            );
        }

        throw new RuntimeException('Missing "jobName" option.');
    }

    private function handleEmail(InputInterface $input): ?int
    {
        $email = $input->getOption('email');
        if (null === $email) {
            throw new RuntimeException('The --email option is required.');
        }

        $importOwner = $this->userManger->findUserByEmail((string)$email);
        if (!$importOwner instanceof User) {
            throw new RuntimeException(sprintf('Invalid email. There is no user with %s email!', $email));
        }

        return $importOwner->getId();
    }
}
