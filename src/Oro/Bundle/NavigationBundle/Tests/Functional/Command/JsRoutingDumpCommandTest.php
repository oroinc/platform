<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Command;

use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\TempDirExtension;

class JsRoutingDumpCommandTest extends WebTestCase
{
    use TempDirExtension;

    private const COMMAND_NAME = 'fos:js-routing:dump';
    private const GAUFRETTE_BASE_PATH = 'gaufrette://public_js/js/';

    private string $tempDir;
    private string $filenamePrefix;
    private FileManager $fileManager;

    protected function setUp(): void
    {
        $this->initClient();
        $this->tempDir = $this->getTempDir('js_routing_dump_command');
        $this->filenamePrefix = self::getContainer()->getParameter('oro_navigation.js_routing_filename_prefix');
        $this->fileManager = self::getContainer()->get('oro_navigation.file_manager.public_js');
    }

    private function doTest(string $targetFilePath, array $commandArguments): void
    {
        $backupFilePaths = [];
        $isGaufretteTarget = str_starts_with($targetFilePath, 'gaufrette://');
        if ($isGaufretteTarget) {
            $files = $this->fileManager->findFiles();
            foreach ($files as $k => $file) {
                $backupFilePath = $this->tempDir . DIRECTORY_SEPARATOR . sprintf('file_%s.bkp', $k);
                $filePath = $this->fileManager->getFilePath($file);
                $backupFilePaths[$filePath] = $backupFilePath;
                $this->moveFile($filePath, $backupFilePath);
            }
        } elseif (file_exists($targetFilePath)) {
            $backupFilePath = $this->tempDir . DIRECTORY_SEPARATOR . 'routes.bkp';
            $backupFilePaths[$targetFilePath] = $backupFilePath;
            $this->moveFile($targetFilePath, $backupFilePath);
        }

        $params = array_map(
            function (string $k, string $v): string {
                return sprintf('%s=%s', $k, $v);
            },
            array_keys($commandArguments),
            array_values($commandArguments)
        );
        try {
            $result = $this->runCommand(self::COMMAND_NAME, $params, true, true);
            self::assertStringContainsString($targetFilePath, $result);
            self::assertFileExists($targetFilePath);
        } finally {
            if ($isGaufretteTarget) {
                $files = $this->fileManager->findFiles();
                foreach ($files as $file) {
                    $filePath = $this->fileManager->getFilePath($file);
                    if (!isset($backupFilePaths[$filePath]) && file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
            }
            foreach ($backupFilePaths as $targetPath => $backupFilePath) {
                $this->moveFile($backupFilePath, $targetPath);
            }
        }
    }

    private function moveFile(string $from, string $to): void
    {
        // the rename() function cannot be used across stream wrappers
        file_put_contents($to, file_get_contents($from));
        unlink($from);
    }

    private function reinitialiseClient(): void
    {
        self::resetClient();
        $this->initClient();
    }

    public function testExecute(): void
    {
        $this->doTest(self::GAUFRETTE_BASE_PATH . $this->filenamePrefix . 'routes.json', []);
    }

    public function testExecuteWithJsFormat(): void
    {
        $this->doTest(
            self::GAUFRETTE_BASE_PATH . $this->filenamePrefix . 'routes.js',
            ['--format' => 'js']
        );
    }

    public function testExecuteWithCustomTarget(): void
    {
        $targetFilePath = $this->tempDir . DIRECTORY_SEPARATOR . 'test_routes.json';
        $this->doTest($targetFilePath, ['--target' => $targetFilePath]);
    }

    public function testExecuteWithCustomTargetWithGaufrettePath(): void
    {
        $targetFilePath = self::GAUFRETTE_BASE_PATH . 'test_routes.json';
        $this->doTest($targetFilePath, ['--target' => $targetFilePath]);
    }

    public function testExecuteWithJsFormatAndCustomTarget(): void
    {
        $targetFilePath = self::GAUFRETTE_BASE_PATH . 'test_routes.txt';
        $this->doTest($targetFilePath, ['--format' => 'js', '--target' => $targetFilePath]);
    }

    public function testUpdatedDefaultOptions(): void
    {
        // re-initialize the client to be sure that the command definition is not cached
        $this->reinitialiseClient();

        $result = $this->runCommand(self::COMMAND_NAME, ['--help'], true, true);
        self::assertStringContainsString('[default: "json"]', $result);
        self::assertStringContainsString(
            '[default: "' . self::GAUFRETTE_BASE_PATH . $this->filenamePrefix . 'routes.json"]',
            $result
        );
    }

    public function testUpdatedDefaultOptionsWhenHelpCommandIsUsed(): void
    {
        // re-initialize the client to be sure that the command definition is not cached
        $this->reinitialiseClient();

        $result = $this->runCommand('help', [self::COMMAND_NAME], true, true);
        self::assertStringContainsString('[default: "json"]', $result);
        self::assertStringContainsString(
            '[default: "' . self::GAUFRETTE_BASE_PATH . $this->filenamePrefix . 'routes.json"]',
            $result
        );
    }
}
