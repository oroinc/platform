<?php

namespace Oro\Bundle\GaufretteBundle\Tests\Functional;

use Oro\Bundle\GaufretteBundle\Command\MigrateFileStorageCommand;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\ReflectionUtil;

class MigrateFileStorageCommandMappingTest extends WebTestCase
{
    public function testMapping()
    {
        self::bootKernel();

        $command = self::getContainer()->get(MigrateFileStorageCommand::class);
        $mappings = ReflectionUtil::getPropertyValue($command, 'mappings');
        $fileManagers = ReflectionUtil::getPropertyValue($command, 'fileManagers');

        $skippedFilesystemsProvider = self::getContainer()->get('oro_gaufrette.tests.skipped_file_systems');

        $gaufretteFileSystemMap = self::getContainer()->get('knp_gaufrette.filesystem_map');
        foreach ($gaufretteFileSystemMap->getIterator() as $fileSystemName => $fileSystem) {
            if (str_starts_with($fileSystemName, 'test_')
                || $skippedFilesystemsProvider->isFileSystemSkipped($fileSystemName)
            ) {
                continue;
            }
            self::assertArrayHasKey(
                $fileSystemName,
                $fileManagers,
                sprintf(
                    'Gaufrette filesystem "%s" was not added to the list of file managers in
                    oro:gaufrette:migrate-filestorages command. 
                    Please add the filesystem to command (use addFileManager method via compiller pas)
                    or add the filesystem to the list of skipped filesystems at this test.',
                    $fileSystemName
                )
            );
            self::assertContains(
                $fileSystemName,
                $mappings,
                sprintf(
                    'Gaufrette filesystem "%s" was not added to the list of mapping paths should be processed in
                    oro:gaufrette:migrate-filestorages command. 
                    Please add the filesystem to command (use addMapping method via compiller pas)
                    or add the filesystem to the list of skipped filesystems at this test.',
                    $fileSystemName
                )
            );
        }
    }
}
