<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Command;

use Oro\Bundle\TranslationBundle\Command\OroTranslationPackCommand;
use Oro\Bundle\TranslationBundle\Tests\Unit\Command\Stubs\TestKernel;
use Oro\Component\Testing\TempDirExtension;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class OroTranslationPackCommandTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

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
        $adapterMock = $this->getNewMock('Oro\Bundle\TranslationBundle\Provider\CrowdinAdapter');

        $adapterMock->expects($this->any())
            ->method('setProjectId')
            ->with($projectId);

        $uploaderMock = $this->getNewMock('Oro\Bundle\TranslationBundle\Provider\TranslationServiceProvider');

        $uploaderMock->expects($this->any())
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
    protected function getCommandMock($methods = array('asText'))
    {
        $commandMock = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Command\OroTranslationPackCommand')
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
