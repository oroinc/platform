<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\EventListener;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class UpdateSchemaListenerTest extends WebTestCase
{
    /**
     * @var Application
     */
    protected $application;

    public function setUp()
    {
        static::createClient();
        $this->application = new Application(self::$kernel);
        $this->application->setAutoExit(false);
    }

    /**
     * @dataProvider commandOptionsProvider
     */
    public function testCommand($commandName, $options, $method, $expectedExitCode)
    {
        $command = new $commandName();
        $this->application->add($command);

        $arguments = array_merge(
            array(
                'command' => $command->getName(),
                '--env'   => self::$kernel->getEnvironment()
            ),
            $options
        );

        $input  = new ArrayInput($arguments);
        $output = new StreamOutput(fopen('php://memory', 'w', false));

        $exitCode = $this->application->run($input, $output);

        $this->assertEquals($expectedExitCode, $exitCode);

        rewind($output->getStream());
        $this->$method(
            'Schema update and create index completed',
            stream_get_contents($output->getStream())
        );
    }

    public function commandOptionsProvider()
    {
        return [
            'otherCommand'             => [
                'commandName'      => 'Doctrine\Bundle\DoctrineBundle\Command\Proxy\InfoDoctrineCommand',
                'options'          => [],
                'method'           => 'assertNotContains',
                'expectedExitCode' => 0
            ],
            'commandWithoutOption'     => [
                'commandName'      => 'Doctrine\Bundle\DoctrineBundle\Command\Proxy\UpdateSchemaDoctrineCommand',
                'options'          => [],
                'method'           => 'assertNotContains',
                'expectedExitCode' => 0
            ],
            'commandWithAnotherOption' => [
                'commandName'      => 'Doctrine\Bundle\DoctrineBundle\Command\Proxy\UpdateSchemaDoctrineCommand',
                'options'          => ['--dump-sql' => true],
                'method'           => 'assertNotContains',
                'expectedExitCode' => 0
            ],
            'commandWithForceOption'   => [
                'commandName'      => 'Doctrine\Bundle\DoctrineBundle\Command\Proxy\UpdateSchemaDoctrineCommand',
                'options'          => ['--force' => true],
                'method'           => 'assertContains',
                'expectedExitCode' => 0
            ]
        ];
    }
}
