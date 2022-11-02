<?php

namespace Oro\Bundle\GaufretteBundle\Command;

use Oro\Bundle\GaufretteBundle\FileManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Moves files from old filesystem storages to FileManager.
 */
class MigrateFileStorageCommand extends Command
{
    /** * @var string */
    protected static $defaultName = 'oro:gaufrette:migrate-filestorages';

    /** @var array [old files path => file manager name] */
    private $mappings = [];

    /** @var array [gaufrette filesystem name => FileManager] */
    private $fileManagers = [];

    /** @var string */
    private $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;

        parent::__construct();
    }

    public function addMapping(string $localPath, string $fileManagerName): void
    {
        $this->mappings[$localPath] = $fileManagerName;
    }

    public function addFileManager(string $fileManagerName, FileManager $fileManager): void
    {
        $this->fileManagers[$fileManagerName] = $fileManager;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this->setDescription('Move application files from old storages to the proper Gaufrette file storages.')
            ->addOption('mode', null, InputOption::VALUE_OPTIONAL, 'Command run mode. Automatic or Manual')
            ->addOption(
                'migration-path',
                null,
                InputOption::VALUE_OPTIONAL,
                'Full path to files should be migrated in Manual mode'
            )
            ->addOption(
                'gaufrette-filesystem',
                null,
                InputOption::VALUE_OPTIONAL,
                'Gaufrette file system where the data should migrate to in Manual mode.'
            )
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command move application files from old storages to the new file storages structure.

  <info>php %command.full_name%</info>
  
  The <info>--mode</info> option can be used to set the mode in non interactive mode.
  If option was not specified, the mode will be asked in interactive mode.

  The command can work in 2 modes: Automatic and Manual.

  In the Automatic mode, the data is migrated to the current structure by a predefined list of paths
  that have been used in the application before v.4.2.

  In the Manual mode, a user is asked for a path to be migrated, as well as the Gaufrette file system name
  where the data should migrate to. 

  The path that has to be migrated can be set with <info>--migration-path</info> option.

  The Gaufrette file system name can be set with <info>--gaufrette-filesystem</info> option.
  To get the list of available file systems, run command with <info>--mode=filesystems-list</info> option.
HELP
            )
            ->addUsage('--mode=<Mode>')
            ->addUsage('--mode=<mode> --migration-path=<migration-path> ')
            ->addUsage('--mode=<mode> --migration-path=<migration-path> --gaufrette-filesystem=<filesystem>');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $mode = $this->getMode($input, $io);

        if ('Cancel' === $mode) {
            $io->comment('Migration was cancelled.');

            return 0;
        }

        if ('Automatic' === $mode) {
            $io->title('Migration started.');
            foreach ($this->mappings as $filesPath => $fileManagerName) {
                $this->migratePath($this->projectDir.$filesPath, $this->fileManagers[$fileManagerName], $io);
            }
        }

        if ('FilesystemsList' === $mode) {
            $io->title('List of available Gaufrette filesystems:');
            foreach (array_keys($this->fileManagers) as $fileSystemName) {
                $io->writeln('- '.$fileSystemName);
            }
        }

        if ('Manual' === $mode) {
            do {
                $path = $this->getMigrationPath($input, $io);
                $fileSystem = $this->getFileSystemName($input, $io);

                $this->migratePath($path, $this->fileManagers[$fileSystem], $io);
            } while (!$input->hasOption('mode') && $io->confirm('Would you like to import another storage?', false));
        }

        return 0;
    }

    private function getMode(InputInterface $input, SymfonyStyle $io): string
    {
        $mode = $input->getOption('mode');
        if (!$mode) {
            $mode = $io->choice(
                'Application files will be moved to the new location, how would you like to processed?',
                ['Cancel', 'Automatic', 'Manual'],
                'Cancel'
            );
        }

        return $mode;
    }

    private function getFileSystemName(InputInterface $input, SymfonyStyle $io): string
    {
        $fileSystem = $input->getOption('gaufrette-filesystem');
        if (!$fileSystem) {
            $fileSystem = $io->choice(
                'Choose Gaufrette file system',
                array_keys($this->fileManagers)
            );
        }

        return $fileSystem;
    }

    private function getMigrationPath(InputInterface $input, SymfonyStyle $io): string
    {
        $path = $input->getOption('migration-path');
        if (!$path) {
            $path = $io->ask('Full path to files should be migrated');
        }

        return $path;
    }

    private function migratePath(string $filesPath, FileManager $fileManager, SymfonyStyle $io): void
    {
        $io->section(sprintf('Migrate path %s.', $filesPath));

        $findPath = realpath($filesPath);
        if (!$findPath) {
            $io->text('Such path does not exist.');

            return;
        }

        if ($fileManager->getLocalPath() === $findPath) {
            $io->text('Path should not be migrated as it was not changed.');

            return;
        }

        $finder = new Finder();
        $finder->files()->in($findPath);
        if (!$finder->hasResults()) {
            $io->text('Path has no files to import.');

            return;
        }

        $movedFiles = $this->moveFilesToStorage($findPath, $finder, $fileManager, $io);
        $io->success(sprintf('Migration finished. %d files moved.', $movedFiles));
    }

    /**
     * @param string       $findPath
     * @param Finder       $finder
     * @param FileManager  $fileManager
     * @param SymfonyStyle $io
     *
     * @return int Number of moved files
     */
    private function moveFilesToStorage(
        string $findPath,
        Finder $finder,
        FileManager $fileManager,
        SymfonyStyle $io
    ): int {
        $filesMoved = 0;
        $fileSystem = new Filesystem();

        $io->progressStart($finder->count());
        foreach ($finder as $file) {
            $absoluteFilePath = $file->getRealPath();
            try {
                $fileManager->writeFileToStorage($absoluteFilePath, str_replace($findPath, '', $absoluteFilePath));
                $fileSystem->remove($absoluteFilePath);
            } catch (\Exception $e) {
                $io->error(sprintf(
                    'Error during migration of %s file. Error: %s',
                    $absoluteFilePath,
                    $e->getMessage()
                ));

                continue;
            }
            $filesMoved++;
            $io->progressAdvance();
        }
        $this->clearDirectories($findPath, $io);
        $io->progressFinish();

        return $filesMoved;
    }

    private function clearDirectories(string $findPath, SymfonyStyle $io): void
    {
        $finder = new Finder();
        $finder->directories()->in($findPath);
        $fileSystem = new Filesystem();
        try {
            $fileSystem->remove($finder);
        } catch (\Exception $e) {
            $io->error(sprintf(
                'Error when deleting directories. Error: %s',
                $e->getMessage()
            ));
        }
    }
}
