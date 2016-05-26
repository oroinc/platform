<?php
namespace Oro\Component\MessageQueue\Tests\Unit\ZeroConfig\Meta;

use Oro\Component\MessageQueue\ZeroConfig\Meta\MetaInfoCommand;
use Oro\Component\MessageQueue\ZeroConfig\Meta\TopicMetaRegistry;
use Oro\Component\Testing\ClassExtensionTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class MetaInfoCommandTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;
    
    public function testShouldBeSubClassOfCommand()
    {
        $this->assertClassExtends(Command::class, MetaInfoCommand::class);
    }
    
    public function testCouldBeConstructedWithTopicMetaRegistryAsFirstArgument()
    {
        new MetaInfoCommand(new TopicMetaRegistry([]));
    }

    public function testShouldShowMessageFoundZeroTopicsIfAnythingInRegistry()
    {
        $command = new MetaInfoCommand(new TopicMetaRegistry([]));

        $output = $this->executeCommand($command);

        $this->assertContains('Found 0 topics', $output);
    }

    public function testShouldShowMessageFoundTwoTopics()
    {
        $command = new MetaInfoCommand(new TopicMetaRegistry([
            'fooTopic' => [],
            'barTopic' => [],
        ]));

        $output = $this->executeCommand($command);

        $this->assertContains('Found 2 topics', $output);
    }

    public function testShouldShowInfoAboutTopics()
    {
        $command = new MetaInfoCommand(new TopicMetaRegistry([
            'fooTopic' => ['description' => 'fooDescription', 'subscribers' => ['fooSubscriber']],
            'barTopic' => ['description' => 'barDescription', 'subscribers' => ['barSubscriber']],
        ]));

        $output = $this->executeCommand($command);

        $this->assertContains('fooTopic', $output);
        $this->assertContains('fooDescription', $output);
        $this->assertContains('fooSubscriber', $output);
        $this->assertContains('barTopic', $output);
        $this->assertContains('barDescription', $output);
        $this->assertContains('barSubscriber', $output);
    }

    /**
     * @param Command $command
     * @param string[] $arguments
     *
     * @return string
     */
    protected function executeCommand(Command $command, array $arguments = array())
    {
        $tester = new CommandTester($command);
        $tester->execute($arguments);

        return $tester->getDisplay();
    }
}
