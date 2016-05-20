<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Command;

use Oro\Bundle\NotificationBundle\Command\MassNotificationCommand;

class MassNotificationCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TitleIndexUpdateCommand
     */
    private $command;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $container;

    protected function setUp()
    {
        $this->command = new MassNotificationCommand();

        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $this->command->setContainer($this->container);
    }

    public function testConfiguration()
    {
        $this->command->configure();

        $this->assertNotEmpty($this->command->getDescription());
        $this->assertNotEmpty($this->command->getName());
    }

    /**
     * @dataProvider provideMethod
     */
    public function testExecute()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $this->command->execute($input, $output);
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function provideMethod()
    {
        return [
            'test title',
            'test body',
        ];
    }
}