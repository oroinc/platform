<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Command;

use Oro\Bundle\TranslationBundle\Command\OroTranslationPackCommand;
use Oro\Bundle\TranslationBundle\Provider\CrowdinAdapter;
use Oro\Bundle\TranslationBundle\Provider\TranslationAdaptersCollection;
use Oro\Bundle\TranslationBundle\Provider\TranslationPackageProvider;
use Oro\Bundle\TranslationBundle\Provider\TranslationPackDumper;
use Oro\Bundle\TranslationBundle\Provider\TranslationServiceProvider;
use Oro\Bundle\TranslationBundle\Tests\Unit\Command\Stubs\TestKernel;
use Oro\Component\Testing\TempDirExtension;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

class OroTranslationPackCommandTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var TranslationPackDumper|\PHPUnit\Framework\MockObject\MockObject */
    private $translationDumper;

    /** var TranslationServiceProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $translationServiceProvider;

    /** var TranslationPackageProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $translationPackageProvider;

    /** @var TranslationAdaptersCollection */
    private $translationAdaptersCollection;

    protected function setUp(): void
    {
        $this->translationDumper = $this->createMock(TranslationPackDumper::class);
        $this->translationServiceProvider = $this->createMock(TranslationServiceProvider::class);
        $this->translationPackageProvider = $this->createMock(TranslationPackageProvider::class);
        $this->translationAdaptersCollection = $this->createMock(TranslationAdaptersCollection::class);
    }

    public function testConfigure()
    {
        $kernel = $this->getKernel();
        $kernel->boot();
        $command = $this->getCommand($kernel);

        $this->assertNotEmpty($command->getDescription());
        $this->assertNotEmpty($command->getDefinition());
        $this->assertNotEmpty($command->getHelp());
    }

    public function testExecuteWhenActionNotSpecified()
    {
        $kernel = $this->getKernel();
        $kernel->boot();
        $command = $this->getCommand($kernel);

        $this->translationDumper->expects($this->never())
            ->method('dump');
        $this->translationServiceProvider->expects($this->never())
            ->method('update');
        $this->translationServiceProvider->expects($this->never())
            ->method('upload');

        $this->executeCommand($command, ['project' => 'SomeProject']);
    }

    public function testExecuteWhenProjectNotSpecified()
    {
        $kernel = $this->getKernel();
        $kernel->boot();
        $command = $this->getCommand($kernel);

        $this->expectException(RuntimeException::class);

        $this->translationDumper->expects($this->never())
            ->method('dump');
        $this->translationServiceProvider->expects($this->never())
            ->method('update');
        $this->translationServiceProvider->expects($this->never())
            ->method('upload');

        $this->executeCommand($command, ['--dump' => true]);
    }

    public function testExecuteForDumpAction()
    {
        $kernel = $this->getKernel();
        $kernel->boot();
        $command = $this->getCommand($kernel);

        $this->translationDumper->expects($this->once())
            ->method('dump');
        $this->translationServiceProvider->expects($this->never())
            ->method('update');
        $this->translationServiceProvider->expects($this->never())
            ->method('upload');

        $this->executeCommand($command, ['--dump' => true, 'project' => 'SomeProject']);
    }

    public function testExecuteForUploadAction()
    {
        $kernel = $this->getKernel();
        $kernel->boot();
        $command = $this->getCommand($kernel);

        $this->translationDumper->expects($this->never())
            ->method('dump');
        $this->translationServiceProvider->expects($this->never())
            ->method('update');
        $this->translationServiceProvider->expects($this->once())
            ->method('upload');

        $this->executeCommand($command, ['--upload' => true, 'project' => 'SomeProject']);
    }

    public function testExecuteForDumpAndUploadActions()
    {
        $kernel = $this->getKernel();
        $kernel->boot();
        $command = $this->getCommand($kernel);

        $this->translationDumper->expects($this->once())
            ->method('dump');
        $this->translationServiceProvider->expects($this->never())
            ->method('update');
        $this->translationServiceProvider->expects($this->once())
            ->method('upload');

        $this->executeCommand($command, ['--upload' => true, '--dump' => true, 'project' => 'SomeProject']);
    }

    public function testUpload()
    {
        $this->runUploadDownloadTest('upload');
    }

    public function testUpdate()
    {
        $this->runUploadDownloadTest('upload', ['-m' => 'update']);
    }

    public function testDownload()
    {
        $this->runUploadDownloadTest('download');
    }

    public function runUploadDownloadTest($commandName, $args = [])
    {
        $kernel = $this->getKernel();
        $kernel->boot();

        $projectId = 'someproject';
        $adapterMock = $this->createMock(CrowdinAdapter::class);

        $adapterMock->expects($this->any())
            ->method('setProjectId')
            ->with($projectId);

        $this->translationServiceProvider->expects($this->once())
            ->method('setLogger')
            ->with($this->isInstanceOf(LoggerInterface::class))
            ->will($this->returnSelf());

        if (isset($args['-m']) && $args['-m'] == 'update') {
            $this->translationServiceProvider->expects($this->once())
                ->method('update');
        } else {
            $this->translationServiceProvider->expects($this->once())
                ->method($commandName);
        }

        $kernel->getContainer()->set('oro_translation.uploader.crowdin_adapter', $adapterMock);

        $command = $this->getCommand($kernel);

        $input = ['--' . $commandName => true, 'project' => $projectId];
        if (!empty($args)) {
            $input = array_merge($input, $args);
        }
        $this->executeCommand($command, $input);
    }

    public function testExecuteWithoutMode()
    {
        $kernel = $this->getKernel();
        $kernel->boot();
        $command = $this->getCommand($kernel);

        $return = $this->executeCommand($command, ['project' => 'test123']);
        $this->assertEquals(1, $return);
    }

    /**
     * @param OroTranslationPackCommand $command
     * @param array                     $input
     *
     * @return int
     */
    private function executeCommand(OroTranslationPackCommand $command, array $input): int
    {
        $tester = new CommandTester($command);

        return $tester->execute(array_merge(['command' => $command->getName()], $input));
    }

    /**
     * @return OroTranslationPackCommand
     */
    private function getCommand(TestKernel $kernel): OroTranslationPackCommand
    {
        $app = new Application($kernel);
        $app->add(new OroTranslationPackCommand(
            $this->translationDumper,
            $this->translationServiceProvider,
            $this->translationPackageProvider,
            $this->translationAdaptersCollection,
            'kernel_dir'
        ));
        $command = $app->find('oro:translation:pack');
        $command->setApplication($app);

        return $command;
    }

    /**
     * @return TestKernel
     */
    private function getKernel(): TestKernel
    {
        return new TestKernel($this->getTempDir('translation-test-stub-cache'));
    }
}
