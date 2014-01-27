<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\EventListener;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Tester\CommandTester;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class UpdateSchemaListenerTest extends WebTestCase
{
    /**
     * @var Application
     */
    protected $application;

    public function setUp()
    {
        $client            = static::createClient();
        $this->application = new Application(self::$kernel);
        $this->application->setAutoExit(false);
    }

    /**
     * @dataProvider commandOptionsProvider
     */
    public function testCommand($commandName, $options, $method)
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

        $input = new ArrayInput($arguments);
        $output = new StreamOutput(fopen('php://memory', 'w', false));

        $exitCode = $this->application->run($input, $output);

        $this->assertEquals(0, $exitCode);

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
                'commandName' => 'Oro\Bundle\SearchBundle\Command\IndexCommand',
                'options'     => [],
                'method'      => 'assertNotContains'
            ],
            'commandWithoutOption'     => [
                'commandName' => 'Doctrine\Bundle\DoctrineBundle\Command\Proxy\UpdateSchemaDoctrineCommand',
                'options'     => [],
                'method'      => 'assertNotContains'
            ],
            'commandWithAnotherOption' => [
                'commandName' => 'Doctrine\Bundle\DoctrineBundle\Command\Proxy\UpdateSchemaDoctrineCommand',
                'options'     => ['--dump-sql' => true],
                'method'      => 'assertNotContains'
            ],
            'commandWithForceOption'   => [
                'commandName' => 'Doctrine\Bundle\DoctrineBundle\Command\Proxy\UpdateSchemaDoctrineCommand',
                'options'     => ['--force' => true],
                'method'      => 'assertContains'
            ]
        ];
    }
}
