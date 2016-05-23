<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Command;

use Oro\Bundle\NotificationBundle\Command\MassNotificationCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class MassNotificationCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MassNotificationCommand
     */
    private $command;

    /**
     * @var Application
     */
    private $application;

    protected function setUp()
    {
        $this->application = new Application();
        $this->command = new MassNotificationCommand();

        $this->application->add($this->command);
    }

    /**
     * @dataProvider provideMethod
     */
    public function testExecute($arg)
    {
        $command = $this->application
                        ->find(MassNotificationCommand::COMMAND_NAME);

        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'command' => $command->getName(),
            'title'   => $arg['title'],
            'body'    => $arg['body']
        ]);
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function provideMethod()
    {
        return [
            [
                [
                    'title' => 'test title',
                    'body'  => 'test body',
                ]
            ]
        ];
    }
}