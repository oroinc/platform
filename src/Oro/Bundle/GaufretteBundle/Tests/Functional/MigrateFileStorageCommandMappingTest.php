<?php

namespace Oro\Bundle\GaufretteBundle\Tests\Functional;

use Oro\Bundle\GaufretteBundle\Command\MigrateFileStorageCommand;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class MigrateFileStorageCommandMappingTest extends WebTestCase
{
    public function testMapping()
    {
        self::bootKernel();

        $command = self::$container->get(MigrateFileStorageCommand::class);
        $mappings = $this->getProperty($command, 'mappings');
        $fileManagers = $this->getProperty($command, 'fileManagers');

        $skippedFilesystemsProvider = self::$container->get('oro_gaufrette.tests.skipped_file_systems');

        $gaufretteFileSystemMap = self::$container->get('knp_gaufrette.filesystem_map');
        foreach ($gaufretteFileSystemMap->getIterator() as $fileSystemName => $fileSystem) {
            if (0 === strpos($fileSystemName, 'test_')
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
            self::assertTrue(
                in_array($fileSystemName, $mappings, true),
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

    protected function getProperty(MigrateFileStorageCommand $object, string $property): array
    {
        $reflection = new \ReflectionProperty(MigrateFileStorageCommand::class, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
