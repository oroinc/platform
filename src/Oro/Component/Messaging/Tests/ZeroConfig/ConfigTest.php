<?php
namespace Oro\Component\Messaging\Tests\ZeroConfig;

use Oro\Component\Messaging\ZeroConfig\Config;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldReturnRouterTopicName()
    {
        $config = new Config('', 'TOPIC','', '', '', '');

        $this->assertSame('topic', $config->getRouterTopicName());
    }

    public function testShouldReturnRouterQueueName()
    {
        $config = new Config('', '','QUEUE', '', '');

        $this->assertSame('queue', $config->getRouterQueueName());
    }

    public function testShouldReturnQueueTopicName()
    {
        $config = new Config('', '','', 'TOPIC', '');

        $this->assertSame('topic', $config->getQueueTopicName());
    }

    public function testShouldReturnDefaultQueueQueueName()
    {
        $config = new Config('', '','', '', 'QUEUE');

        $this->assertSame('queue', $config->getDefaultQueueQueueName());
    }

    public function testFormatNameShouldLowercaseName()
    {
        $config = new Config('', '','', '', '', '');

        $this->assertSame('lowercase', $config->formatName('LOWERCASE'));
    }

    public function testFormatNameShouldAddPrefix()
    {
        $config = new Config('prefix', '','', '', '', '');

        $this->assertSame('prefix.name', $config->formatName('name'));
    }

    public function testFormatNameShouldRemoveDotsFromBeginningAndEnd()
    {
        $config = new Config('.prefix', '','', '', '', '');

        $this->assertSame('prefix.name', $config->formatName('name.'));
    }
}
