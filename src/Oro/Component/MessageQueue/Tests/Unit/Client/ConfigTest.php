<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client;

use Oro\Component\MessageQueue\Client\Config;

class ConfigTest extends \PHPUnit\Framework\TestCase
{
    private Config $config;

    protected function setUp(): void
    {
        $this->config = new Config(
            'aPrefix',
            'aDefaultQueueName',
            'aDefaultTopicName'
        );
    }

    public function testShouldReturnDefaultQueueNameSetInConstructor(): void
    {
        self::assertEquals('aprefix.adefaultqueuename', $this->config->getDefaultQueueName());
    }

    public function testShouldReturnDefaultTopicNameSetInConstructor(): void
    {
        self::assertEquals('aprefix.adefaulttopicname', $this->config->getDefaultTopicName());
    }

    /**
     * @dataProvider queueNameDataProvider
     *
     * @param string $queueName
     */
    public function testAddTransportPrefix(string $transportPrefix, string $queueName): void
    {
        $config = new Config(
            $transportPrefix,
            'aDefaultQueueName',
            'aDefaultTopicName'
        );

        self::assertEquals('aprefix.samplequeue', $config->addTransportPrefix($queueName));
    }

    public function queueNameDataProvider(): array
    {
        return [
            ['aprefix', 'samplequeue'],
            ['APREFIX', 'SAMPLEQUEUE'],
            ['APREFIX', 'SAMPLEQUEUE.'],
            [' APREFIX ', ' SAMPLEQUEUE. '],
        ];
    }

    /**
     * @dataProvider transportQueueNameDataProvider
     *
     * @param string $queueName
     */
    public function testRemoveTransportPrefix(string $transportPrefix, string $transportQueueName): void
    {
        $config = new Config(
            $transportPrefix,
            'aDefaultQueueName',
            'aDefaultTopicName'
        );

        self::assertEquals('samplequeue', $config->removeTransportPrefix($transportQueueName));
    }

    public function transportQueueNameDataProvider(): array
    {
        return [
            ['aprefix', 'samplequeue'],
            ['aprefix', 'aprefix.samplequeue'],
            ['APREFIX', 'APREFIX.SAMPLEQUEUE'],
            [' APREFIX ', ' APREFIX.SAMPLEQUEUE '],
        ];
    }
}
