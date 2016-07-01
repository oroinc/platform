<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Command;

use Oro\Bundle\LayoutBundle\Command\AssetsThemeDumpCommand;

class AssetsThemeDumpCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AssetsThemeDumpCommand
     */
    private $command;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $container;

    protected function setUp()
    {
        $this->command = new AssetsThemeDumpCommand();

        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $this->command->setContainer($this->container);
    }

    public function testConfiguration()
    {
        $this->assertNotEmpty($this->command->getDescription());
        $this->assertNotEmpty($this->command->getName());
    }

    /**
     * @dataProvider provideMethod
     * @param string $name
     */
    public function testExecute($name)
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $input->expects($this->exactly(0))
            ->method('addArgument')
            ->willReturnMap([
                ['name', $name]
            ]);

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
            'no name' => [
                'name' => ''
            ],
            'valid name' => [
                'name' => 'default'
            ],
            'invalid name' => [
                'name' => 'sometest'
            ],
        ];
    }
}
