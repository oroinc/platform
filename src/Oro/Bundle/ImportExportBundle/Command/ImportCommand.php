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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Imports data from a CSV file.
 */
class ImportCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:import:file';

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
    protected function configure()
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'CSV file name')
            ->addOption('jobName', null, InputOption::VALUE_REQUIRED, 'Import job name')
            ->addOption('processor', null, InputOption::VALUE_REQUIRED, 'Import processor name')
            ->addOption('validation', null, InputOption::VALUE_NONE, 'Only validate data instead of import')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Email to send the import log to')
            ->setDescription('Imports data from a CSV file.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command imports data from a CSV file.
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

        $this->messageProducer->send(
            PreImportTopic::getName(),
            [
                'fileName' => $fileName,
                'originFileName' => $originFileName,
                'userId' => $importOwner->getId(),
                'jobName' =>  $jobName,
                'processorAlias' => $processorAlias,
                'process' => $processor,
            ]
        );

        if ($email) {
            $output->writeln('Scheduled successfully. The result will be sent to the email');
        } else {
            $output->writeln('Scheduled successfully.');
        }

        return 0;
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function handleProcessorName(
        InputInterface $input,
        OutputInterface $output,
        string $type = ProcessorRegistry::TYPE_IMPORT
    ): string {
        $label = ucwords(str_replace('-', ' ', 'processor'));

        if ($input->getOption('processor') &&
            $this->processorRegistry->hasProcessor($type, $input->getOption('processor'))
        ) {
            return $input->getOption('processor');
        }

        $processors = $this->processorRegistry->getProcessorsByType($type);

        if (!$processors) {
            throw new \InvalidArgumentException('No configured processors');
        }

        $processorNames = array_keys($processors);
        if (!$input->getOption('no-interaction')) {
            $question = new ChoiceQuestion(sprintf('<question>Choose %s: </question>', $label), $processorNames);
            return $this->getHelper('question')->ask($input, $output, $question);
        }

        throw new \InvalidArgumentException(sprintf('Missing %s', $label));
    }

    private function handleJobName(
        InputInterface $input,
        OutputInterface $output,
        string $type = ProcessorRegistry::TYPE_IMPORT
    ): string {
        $jobName = $input->getOption('jobName');
        $jobNames = array_keys($this->connectorRegistry->getJobs($type)['oro_importexport']);
        if ($jobName && in_array($jobName, $jobNames)) {
            return $jobName;
        }
        if (!$input->getOption('no-interaction')) {
            $question = new ChoiceQuestion('<question>Choose Job: </question>', $jobNames);
            return $this->getHelper('question')->ask($input, $output, $question);
        }

        throw new \InvalidArgumentException('Missing "jobName" option.');
    }
}
