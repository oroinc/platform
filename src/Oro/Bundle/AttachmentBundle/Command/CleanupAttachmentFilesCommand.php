<?php

namespace Oro\Bundle\AttachmentBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\GaufretteBundle\FileManager;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * The command to delete lost attachment files.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class CleanupAttachmentFilesCommand extends Command implements SignalableCommandInterface
{
    private const FILE_REMOVED = 'The attachment file "{fileName}" has been removed'
    . ' because it does not have linked attachment entity in the database.';
    private const FILE_REMOVE_FAILED = 'The attachment file "{fileName}"'
    . ' does not have linked attachment entity in the database but removing this file failed.';
    private const FILE_TO_BE_REMOVED = 'The attachment file "{fileName}" should be removed'
    . ' because it does not have linked attachment entity in the database.';
    private const MISSING_FILE = 'The attachment entity with ID = {entityId}'
    . ' (entity: {parentEntityClass}, field: {parentEntityFieldName})'
    . ' is linked to the file "{fileName}" but this file does not exist.';

    private const COLLECT_FILES_REPORTING_BATCH_SIZE = 50000;
    private const CHECK_FILES_REPORTING_BATCH_SIZE = 50000;
    private const CHECK_ENTITIES_REPORTING_BATCH_SIZE = 10000;

    private const TMP_TABLE_NAME = 'tmp_oro_attachment_file_cleanup';

    private int $collectAttachmentFilesBatchSize;
    private int $loadAttachmentsBatchSize;
    private int $loadExistingAttachmentsBatchSize;
    private FileManager $dataFileManager;
    private ManagerRegistry $doctrine;
    private FileManager $attachmentFileManager;
    private bool $needToRemoveSavedBatches = false;

    public function __construct(
        int $collectAttachmentFilesBatchSize,
        int $loadAttachmentsBatchSize,
        int $loadExistingAttachmentsBatchSize,
        FileManager $dataFileManager,
        ManagerRegistry $doctrine,
        FileManager $attachmentFileManager
    ) {
        $this->collectAttachmentFilesBatchSize = $collectAttachmentFilesBatchSize;
        $this->loadAttachmentsBatchSize = $loadAttachmentsBatchSize;
        $this->loadExistingAttachmentsBatchSize = $loadExistingAttachmentsBatchSize;
        $this->dataFileManager = $dataFileManager;
        $this->doctrine = $doctrine;
        $this->attachmentFileManager = $attachmentFileManager;
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    public function getSubscribedSignals(): array
    {
        return [\SIGINT, \SIGTERM];
    }

    /**
     * {@inheritDoc}
     */
    public function handleSignal(int $signal): void
    {
        if (\SIGINT === $signal || \SIGTERM === $signal) {
            if ($this->needToRemoveSavedBatches) {
                $this->dataFileManager->deleteAllFiles();
            }
            exit(0);
        }
    }

    /**
     * {@inheritDoc}
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force the execution')
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'Show lost attachment files instead of actual deletion of them'
            )
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command deletes lost attachment files.

  <info>php %command.full_name% --force</info>

The <info>--dry-run</info> option can be used to see lost attachment files
instead of actual deletion of them:

  <info>php %command.full_name% --dry-run</info>

HELP
            )
            ->addUsage('--force')
            ->addUsage('--dry-run');
    }

    /**
     * {@inheritDoc}
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = $input->getOption('force');
        $dryRun = $input->getOption('dry-run');

        $hasErrors = false;
        if ($force || $dryRun) {
            $this->createTemporaryTable();
            try {
                $hasErrors = $this->process($io, $output, $dryRun);
            } finally {
                $this->deleteTemporaryTable();
            }
        } else {
            $io->text('To force execution run command with <info>--force</info> option:');
            $io->text(sprintf('    <info>%s --force</info>', $this->getName()));
        }

        return $hasErrors ? 1 : 0;
    }

    abstract protected function getAttachmentFileNames(): iterable;

    private function process(StyleInterface $io, OutputInterface $output, bool $dryRun): bool
    {
        $verbose = $output->isVerbose();
        $logger = $this->getConsoleLogger($output);

        $hasErrors = false;
        $io->comment('Collecting attachment files to cleanup...');
        $dataFileNames = $this->collectBatches($io, $dryRun, $verbose);
        $io->comment('Filling a temporary table with attachment files...');
        $this->fillTemporaryTable();
        if ($dryRun) {
            $io->comment('Checking if attachment files are linked to existing attachment entities...');
            $errorCount = $this->checkAttachmentFilesWithoutEntities($dataFileNames, $logger, $io, $verbose);
            if ($errorCount > 0) {
                $hasErrors = true;
            }
            $io->comment('Checking if attachment entities are linked to existing attachment files...');
            $errorCount = $this->checkAttachmentEntitiesWithoutFiles($logger, $io, $verbose);
            if ($errorCount > 0) {
                $hasErrors = true;
            }
            if (!$hasErrors) {
                $io->success('The attachment files to be cleaned up were not found.');
            }
        } else {
            $io->comment('Cleaning up attachment files...');
            $this->cleanupAttachmentFiles($dataFileNames, $logger, $io, $verbose);
            $io->success('The attachment files were successfully cleaned up.');
        }

        return $hasErrors;
    }

    private function collectBatches(StyleInterface $io, bool $dryRun, bool $verbose): array
    {
        $this->dataFileManager->deleteAllFiles();

        $dataFileNames = [];
        $batch = [];
        $counter = 0;
        $fileNameIterator = $this->getAttachmentFileNames();
        foreach ($fileNameIterator as $fileName) {
            $counter++;
            $batch[] = $fileName;
            if ($counter % $this->collectAttachmentFilesBatchSize === 0) {
                $dataFileNames[] = $this->saveBatch($batch, $counter);
                $batch = [];
            }
            if ($verbose && ($counter % self::COLLECT_FILES_REPORTING_BATCH_SIZE === 0)) {
                $io->text(sprintf('%d files collected...', $counter));
            }
        }
        if ($batch) {
            $dataFileNames[] = $this->saveBatch($batch, $counter);
        }
        if ($verbose && ($counter % self::COLLECT_FILES_REPORTING_BATCH_SIZE !== 0)) {
            $io->text(sprintf('%d files collected...', $counter));
        }

        $io->comment(sprintf(
            'The number of collected attachment files to be checked: %d',
            $counter
        ));
        if (!$dryRun) {
            $io->comment(sprintf(
                'The number of batches to be be processed: %d',
                $this->getBatchNumber($counter)
            ));
        }

        return $dataFileNames;
    }

    private function saveBatch(array $fileNames, int $counter): string
    {
        $dataFileName = sprintf(
            'attachment_files_cleanup_%s_%d.json',
            str_replace('.', '', uniqid('', true)),
            $this->getBatchNumber($counter)
        );
        $this->dataFileManager->writeToStorage(
            json_encode($fileNames, JSON_THROW_ON_ERROR),
            $dataFileName
        );
        $this->needToRemoveSavedBatches = true;

        return $dataFileName;
    }

    private function loadBatch(string $dataFileName): array
    {
        $data = $this->dataFileManager->getFileContent($dataFileName);
        if (null === $data) {
            return [];
        }

        return json_decode($data, false, 2, JSON_THROW_ON_ERROR);
    }

    private function deleteBatch(string $dataFileName, StyleInterface $io): void
    {
        try {
            $this->dataFileManager->deleteFile($dataFileName);
        } catch (\RuntimeException $e) {
            $io->error(sprintf(
                'Unable to remove the temporary file "%s" contains a list of attachment files. Reason: %s',
                $dataFileName,
                $e->getMessage()
            ));
        }
    }

    private function getBatchNumber(int $counter): int
    {
        return floor(($counter - 1) / $this->collectAttachmentFilesBatchSize) + 1;
    }

    private function getConsoleLogger(OutputInterface $output): LoggerInterface
    {
        return new ConsoleLogger($output, [
            LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::INFO   => OutputInterface::VERBOSITY_VERBOSE,
            LogLevel::DEBUG  => OutputInterface::VERBOSITY_VERY_VERBOSE
        ], [
            LogLevel::WARNING => ConsoleLogger::ERROR
        ]);
    }

    private function cleanupAttachmentFiles(
        array $dataFileNames,
        LoggerInterface $logger,
        StyleInterface $io,
        bool $verbose
    ): void {
        $totalCounter = 0;
        foreach ($dataFileNames as $dataFileName) {
            $fileNames = $this->loadBatch($dataFileName);
            try {
                $this->processBatchForCleanupAttachmentFiles($fileNames, $totalCounter, $logger, $io, $verbose);
            } catch (\Throwable $e) {
                $logger->error('Unable to remove lost attachment files.', ['exception' => $e]);
            } finally {
                $this->deleteBatch($dataFileName, $io);
            }
        }
        if ($verbose && ($totalCounter % self::CHECK_FILES_REPORTING_BATCH_SIZE !== 0)) {
            $io->text(sprintf('%d files checked...', $totalCounter));
        }
    }

    private function processBatchForCleanupAttachmentFiles(
        array $fileNames,
        int &$totalCounter,
        LoggerInterface $logger,
        StyleInterface $io,
        bool $verbose
    ): void {
        $batch = [];
        $counter = 0;
        foreach ($fileNames as $fileName) {
            $counter++;
            $batch[] = $fileName;
            if ($counter % $this->loadExistingAttachmentsBatchSize === 0) {
                $this->processSubBatchForCleanupAttachmentFiles($batch, $totalCounter, $logger, $io, $verbose);
                $batch = [];
            }
        }
        if ($batch) {
            $this->processSubBatchForCleanupAttachmentFiles($batch, $totalCounter, $logger, $io, $verbose);
        }
    }

    private function processSubBatchForCleanupAttachmentFiles(
        array $fileNames,
        int &$totalCounter,
        LoggerInterface $logger,
        StyleInterface $io,
        bool $verbose
    ): void {
        $existingAttachments = $this->loadExistingAttachments($fileNames);
        foreach ($fileNames as $fileName) {
            $totalCounter++;
            if (!isset($existingAttachments[$fileName])) {
                try {
                    $this->attachmentFileManager->deleteFile($fileName);
                    $logger->warning(self::FILE_REMOVED, ['fileName' => $fileName]);
                } catch (\RuntimeException $e) {
                    $logger->error(self::FILE_REMOVE_FAILED, ['fileName' => $fileName, 'exception' => $e]);
                }
            }
            if ($verbose && ($totalCounter % self::CHECK_FILES_REPORTING_BATCH_SIZE === 0)) {
                $io->text(sprintf('%d files checked...', $totalCounter));
            }
        }
    }

    private function checkAttachmentFilesWithoutEntities(
        array $dataFileNames,
        LoggerInterface $logger,
        StyleInterface $io,
        bool $verbose
    ): int {
        $errorCount = 0;
        $totalCounter = 0;
        foreach ($dataFileNames as $dataFileName) {
            try {
                $errorCount = $this->processFilesForCheckAttachmentFilesWithoutEntities(
                    $this->loadBatch($dataFileName),
                    $totalCounter,
                    $logger,
                    $io,
                    $verbose
                );
            } finally {
                $this->deleteBatch($dataFileName, $io);
            }
        }
        if ($verbose && ($totalCounter % self::CHECK_FILES_REPORTING_BATCH_SIZE !== 0)) {
            $io->text(sprintf('%d files checked...', $totalCounter));
        }
        $this->needToRemoveSavedBatches = false;

        return $errorCount;
    }

    private function processFilesForCheckAttachmentFilesWithoutEntities(
        array $fileNames,
        int &$totalCounter,
        LoggerInterface $logger,
        StyleInterface $io,
        bool $verbose
    ): int {
        $errorCount = 0;
        $batch = [];
        $counter = 0;
        foreach ($fileNames as $fileName) {
            $counter++;
            $totalCounter++;
            $batch[] = $fileName;
            if ($counter % $this->loadExistingAttachmentsBatchSize === 0) {
                $errorCount += $this->processBatchForCheckAttachmentFilesWithoutEntities($batch, $logger);
                $batch = [];
            }
            if ($verbose && ($totalCounter % self::CHECK_FILES_REPORTING_BATCH_SIZE === 0)) {
                $io->text(sprintf('%d files checked...', $totalCounter));
            }
        }
        if ($batch) {
            $errorCount += $this->processBatchForCheckAttachmentFilesWithoutEntities($batch, $logger);
        }

        return $errorCount;
    }

    private function processBatchForCheckAttachmentFilesWithoutEntities(array $fileNames, LoggerInterface $logger): int
    {
        $errorCount = 0;
        $existingAttachments = $this->loadExistingAttachments($fileNames);
        foreach ($fileNames as $fileName) {
            if (!isset($existingAttachments[$fileName])) {
                $logger->warning(self::FILE_TO_BE_REMOVED, ['fileName' => $fileName]);
                $errorCount++;
            }
        }

        return $errorCount;
    }

    private function checkAttachmentEntitiesWithoutFiles(
        LoggerInterface $logger,
        StyleInterface $io,
        bool $verbose
    ): int {
        $counter = 0;
        $errorCount = 0;
        $iterator = $this->loadAttachments();
        foreach ($iterator as $row) {
            $fileName = $row['filename'];
            if (!$this->attachmentFileManager->hasFile($fileName)) {
                $logger->warning(
                    self::MISSING_FILE,
                    array_merge(
                        ['fileName' => $fileName, 'entityId' => $row['id']],
                        $this->loadAttachmentDetails($row['id'])
                    )
                );
                $errorCount++;
            }
            $counter++;
            if ($verbose && ($counter % self::CHECK_ENTITIES_REPORTING_BATCH_SIZE === 0)) {
                $io->text(sprintf('%d entities checked...', $counter));
            }
        }
        if ($verbose && ($counter % self::CHECK_ENTITIES_REPORTING_BATCH_SIZE !== 0)) {
            $io->text(sprintf('%d entities checked...', $counter));
        }

        return $errorCount;
    }

    private function loadAttachments(): \Iterator
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(File::class);
        $qb = $em->createQueryBuilder()
            ->from(File::class, 'f')
            ->select('f.id, f.filename');

        $iterator = new BufferedQueryResultIterator($qb);
        $iterator->setBufferSize($this->loadAttachmentsBatchSize);

        return $iterator;
    }

    private function loadAttachmentDetails(int $entityId): array
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(File::class);
        $rows = $em->createQueryBuilder()
            ->from(File::class, 'f')
            ->select('f.parentEntityClass, f.parentEntityFieldName')
            ->where('f.id = :id')
            ->setParameter('id', $entityId)
            ->getQuery()
            ->getArrayResult();

        return $rows ? reset($rows) : [];
    }

    /**
     * @param string[] $fileNames
     *
     * @return array [file name => true, ...]
     */
    private function loadExistingAttachments(array $fileNames): array
    {
        $params = implode(',', array_fill(0, \count($fileNames), '?'));
        $qb = $this->getDbalConnection()
            ->createQueryBuilder()
            ->from($this->getTemporaryTableName(), 'f')
            ->select('f.filename')
            ->where('f.filename IN (' . $params . ')');
        $i = 0;
        foreach ($fileNames as $fileName) {
            $i++;
            $qb->setParameter($i, $fileName, ParameterType::STRING);
        }
        $rows = $qb->execute();
        $existingAttachments = [];
        foreach ($rows as $row) {
            $existingAttachments[$row['filename']] = true;
        }

        return $existingAttachments;
    }

    private function createTemporaryTable(): void
    {
        $this->getDbalConnection()->executeQuery($this->getCreateTemporaryTableSql());
    }

    private function deleteTemporaryTable(): void
    {
        $this->getDbalConnection()->executeQuery($this->getDeleteTemporaryTableSql());
    }

    private function fillTemporaryTable(): void
    {
        $dbalConnection = $this->getDbalConnection();
        $dbalConnection->executeQuery(sprintf(
            'INSERT INTO %s (filename) SELECT filename FROM oro_attachment_file',
            $this->getTemporaryTableName()
        ));
        $dbalConnection->executeQuery($this->getCreateTemporaryTableIndexSql());
    }

    private function getCreateTemporaryTableSql(): string
    {
        $dbalPlatform = $this->getDbalPlatform();

        return sprintf(
            '%s %s (%s)',
            $dbalPlatform->getCreateTemporaryTableSnippetSQL(),
            $this->getTemporaryTableName(),
            $dbalPlatform->getColumnDeclarationListSQL([
                'filename' => ['notnull' => true, 'type' => Type::getType(Types::STRING), 'length' => 255]
            ])
        );
    }

    private function getCreateTemporaryTableIndexSql(): string
    {
        return $this->getDbalPlatform()->getCreateIndexSQL(
            new Index(self::TMP_TABLE_NAME . '_idx', ['filename']),
            $this->getTemporaryTableName()
        );
    }

    private function getDeleteTemporaryTableSql(): string
    {
        return $this->getDbalPlatform()->getDropTemporaryTableSQL($this->getTemporaryTableName());
    }

    private function getTemporaryTableName(): string
    {
        return $this->getDbalPlatform()->getTemporaryTableName(self::TMP_TABLE_NAME);
    }

    private function getDbalConnection(): Connection
    {
        return $this->doctrine->getConnection();
    }

    private function getDbalPlatform(): AbstractPlatform
    {
        return $this->getDbalConnection()->getDatabasePlatform();
    }
}
