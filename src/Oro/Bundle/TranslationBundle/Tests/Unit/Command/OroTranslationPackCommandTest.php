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

    protected function setUp()
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
        $app = new Application($kernel);
        $app->add($this->getCommandMock());
        $command = $app->find('oro:translation:pack');

        $this->assertNotEmpty($command->getDescription());
        $this->assertNotEmpty($command->getDefinition());
        $this->assertNotEmpty($command->getHelp());
    }

    /**
     * Test command execute
     *
     * @dataProvider executeInputProvider
     *
     * @param array       $input
     * @param array       $expectedCalls
     * @param bool|string $exception
     */
    public function testExecute($input, $expectedCalls = array(), $exception = false)
    {
        $kernel = $this->getKernel();
        $kernel->boot();
        $app         = new Application($kernel);
        $commandMock = $this->getCommandMock(array_keys($expectedCalls));
        $app->add($commandMock);
        $command = $app->find('oro:translation:pack');
        $command->setApplication($app);

        if ($exception) {
            $this->expectException($exception);
        }

        $transServiceMock = $this->getMockBuilder(
            'Oro\Bundle\TranslationBundle\Provider\TranslationServiceProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();

        foreach ($expectedCalls as $method => $count) {
            if ($method == 'getTranslationService') {
                $commandMock->expects($this->exactly($count))
                    ->method($method)
                    ->will($this->returnValue($transServiceMock));
            }
            $commandMock->expects($this->exactly($count))->method($method);
        }

        $tester = new CommandTester($command);
        $input += array('command' => $command->getName());
        $tester->execute($input);
    }

    /**
     * @return array
     */
    public function executeInputProvider()
    {
        return array(
            'error if action not specified'         => array(
                array('project' => 'SomeProject'),
                array(
                    'dump'   => 0,
                    'upload' => 0
                )
            ),
            'error if project not specified'        => array(
                array('--dump' => true),
                array(
                    'dump'   => 0,
                    'upload' => 0
                ),
                '\RuntimeException'
            ),
            'dump action should perform'            => array(
                array('--dump' => true, 'project' => 'SomeProject'),
                array(
                    'dump'   => 1,
                    'upload' => 0
                ),
            ),
            'upload action should perform'          => array(
                array('--upload' => true, 'project' => 'SomeProject'),
                array(
                    'dump'                  => 0,
                    'upload'                => 1,
                    'getTranslationService' => 1,
                    'getLangPackDir'        => 1,
                ),
            ),
            'dump and upload action should perform' => array(
                array('--upload' => true, '--dump' => true, 'project' => 'SomeProject'),
                array(
                    'dump'   => 1,
                    'upload' => 1,
                    'getTranslationService' => 1,
                ),
            )
        );
    }

    public function testUpload()
    {
        $this->runUploadDownloadTest('upload');
    }

    public function testUpdate()
    {
        $this->runUploadDownloadTest('upload', array('-m' => 'update'));
    }

    public function testDownload()
    {
        $this->runUploadDownloadTest('download');
    }

    public function runUploadDownloadTest($commandName, $args = [])
    {
        $kernel = $this->getKernel();
        $kernel->boot();

        $projectId   = 'someproject';
        $adapterMock = $this->getNewMock(CrowdinAdapter::class);

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

        $app         = new Application($kernel);
        $commandMock = $this->getCommandMock();
        $app->add($commandMock);

        $command = $app->find('oro:translation:pack');
        $command->setApplication($app);

        $tester = new CommandTester($command);
        $input  = array('command' => $command->getName(), '--' . $commandName => true, 'project' => $projectId);
        if (!empty($args)) {
            $input = array_merge($input, $args);
        }

        $tester->execute($input);
    }

    public function testExecuteWithoutMode()
    {
        $kernel = $this->getKernel();
        $kernel->boot();

        $app         = new Application($kernel);
        $commandMock = $this->getCommandMock();
        $app->add($commandMock);

        $command = $app->find('oro:translation:pack');
        $command->setApplication($app);

        $tester = new CommandTester($command);
        $input  = array('command' => $command->getName(), 'project' => 'test123');

        $return = $tester->execute($input);
        $this->assertEquals(1, $return);
    }

    /**
     * @return array
     */
    public function formatProvider()
    {
        return array(
            'format do not specified, yml default' => array('yml', false),
            'format specified xml expected '       => array('xml', 'xml')
        );
    }

    /**
     * Prepares command mock
     * asText mocked by default in case when we don't need to mock anything
     *
     * @param array $methods
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|OroTranslationPackCommand
     */
    protected function getCommandMock($methods = ['asText'])
    {
        $commandMock = $this->getMockBuilder(OroTranslationPackCommand::class)
            ->setConstructorArgs(
                [
                    $this->translationDumper,
                    $this->translationServiceProvider,
                    $this->translationPackageProvider,
                    $this->translationAdaptersCollection,
                    'kernel_dir'
                ]
            )
            ->setMethods($methods);

        return $commandMock->getMock();
    }

    /**
     * @param $class
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getNewMock($class)
    {
        return $this->createMock($class);
    }

    /**
     * @return TestKernel
     */
    private function getKernel()
    {
        return new TestKernel($this->getTempDir('translation-test-stub-cache'));
    }
}
