<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Command;

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Translation\Extractor\ChainExtractor;
use Symfony\Bundle\FrameworkBundle\Translation\TranslationLoader;

use Oro\Bundle\TranslationBundle\Tests\Unit\Command\Stubs\TestKernel;
use Oro\Bundle\TranslationBundle\Command\OroTranslationPackCommand;

class OroTranslationPackCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testConfigure()
    {
        $kernel = new TestKernel();
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
        $kernel = new TestKernel();
        $kernel->boot();
        $app         = new Application($kernel);
        $commandMock = $this->getCommandMock(array_keys($expectedCalls));
        $app->add($commandMock);
        $command = $app->find('oro:translation:pack');
        $command->setApplication($app);

        if ($exception) {
            $this->setExpectedException($exception);
        }
        foreach ($expectedCalls as $method => $count) {
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
                    'dump'   => 0,
                    'upload' => 1
                ),
            ),
            'dump and upload action should perform' => array(
                array('--upload' => true, '--dump' => true, 'project' => 'SomeProject'),
                array(
                    'dump'   => 1,
                    'upload' => 1
                ),
            )
        );
    }

    /**
     *
     * @dataProvider formatProvider
     *
     * @param string $expectedFormat
     * @param string $inputFormat
     */
    public function testDump($expectedFormat, $inputFormat)
    {
        $kernel = new TestKernel();
        $kernel->boot();

        $phpUnit    = $this;
        $writerMock = $this->getMock('Symfony\Component\Translation\Writer\TranslationWriter');
        $writerMock->expects($this->once())->method('writeTranslations')->will(
            $this->returnCallback(
                function ($result, $format, $path) use ($phpUnit, $expectedFormat) {
                    $phpUnit->assertTrue(
                        strpos($path['path'], 'language-pack/SomeProject/SomeBundle/translations') !== false
                    );

                    $phpUnit->assertEquals($format, $expectedFormat);
                }
            )
        );

        $extractor = new ChainExtractor();
        $kernel->getContainer()->set('translation.writer', $writerMock);
        $kernel->getContainer()->set('translation.loader', new TranslationLoader());
        $kernel->getContainer()->set('translation.extractor', $extractor);

        $app         = new Application($kernel);
        $commandMock = $this->getCommandMock(array('createDirectory'));
        $commandMock->expects($this->once())->method('createDirectory');
        $app->add($commandMock);
        $command = $app->find('oro:translation:pack');
        $command->setApplication($app);

        $tester = new CommandTester($command);
        $input  = array('command' => $command->getName(), '--dump' => true, 'project' => 'SomeProject');
        if ($inputFormat) {
            $input['--output-format'] = $inputFormat;
        }
        $tester->execute($input);
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
        $kernel = new TestKernel();
        $kernel->boot();

        $projectId = 'someproject';
        $adapterMock = $this->getNewMock('Oro\Bundle\TranslationBundle\Provider\CrowdinAdapter');

        $adapterMock->expects($this->once())
            ->method('setProjectId')
            ->with($projectId);

        $uploaderMock = $this->getNewMock('Oro\Bundle\TranslationBundle\Provider\TranslationServiceProvider');

        $uploaderMock->expects($this->once())
            ->method('setAdapter')
            ->with($adapterMock)
            ->will($this->returnSelf());

        $uploaderMock->expects($this->once())
            ->method('setLogger')
            ->with($this->isInstanceOf('Psr\Log\LoggerInterface'))
            ->will($this->returnSelf());

        if (isset($args['-m']) && $args['-m'] == 'update') {
            $uploaderMock->expects($this->once())
                ->method('update');
        } else {
            $uploaderMock->expects($this->once())
                ->method($commandName);
        }

        $kernel->getContainer()->set('oro_translation.uploader.crowdin_adapter', $adapterMock);
        $kernel->getContainer()->set('oro_translation.service_provider', $uploaderMock);

        $app         = new Application($kernel);
        $commandMock = $this->getCommandMock();
        $app->add($commandMock);

        $command = $app->find('oro:translation:pack');
        $command->setApplication($app);

        $tester = new CommandTester($command);
        $input  = array('command' => $command->getName(), '--'.$commandName => true, 'project' => $projectId);
        if (!empty($args)) {
            $input = array_merge($input, $args);
        }

        $tester->execute($input);
    }

    public function testExecuteWithoutMode()
    {
        $kernel = new TestKernel();
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

    public function testCreateDirectory()
    {
        $dirPath = sys_get_temp_dir() . '/testDir';
        $cmd     = $this->getCommandMock();

        $class  = new \ReflectionClass($cmd);
        $method = $class->getMethod('createDirectory');
        $method->setAccessible(true);

        $method->invokeArgs($cmd, array($dirPath));

        $this->assertTrue(is_dir($dirPath));
        rmdir($dirPath);
    }

    /**
     * Prepares command mock
     * asText mocked by default in case when we don't need to mock anything
     *
     * @param array $methods
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|OroTranslationPackCommand
     */
    protected function getCommandMock($methods = array('asText'))
    {
        $commandMock = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Command\OroTranslationPackCommand')
            ->setMethods($methods);

        return $commandMock->getMock();
    }

    /**
     * @param $class
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getNewMock($class)
    {
        return $this->getMock($class, [], [], '', false);
    }
}
