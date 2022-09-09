<?php

namespace Oro\Component\MessageQueue\Test;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Base abstract class for testing message queue topic.
 */
abstract class AbstractTopicTestCase extends \PHPUnit\Framework\TestCase
{
    protected TopicInterface $topic;

    protected function setUp(): void
    {
        $this->topic = $this->getTopic();
    }

    abstract protected function getTopic(): TopicInterface;

    protected function assertBodyValid(array $expectedBody, array $body): void
    {
        $optionsResolver = new OptionsResolver();
        $this->topic->configureMessageBody($optionsResolver);

        self::assertEquals($expectedBody, $optionsResolver->resolve($body));
    }

    protected function assertBodyInvalid(
        array $body,
        string $expectedExceptionClass,
        string $expectedExceptionMessageRegEx = ''
    ): void {
        $optionsResolver = new OptionsResolver();
        $this->topic->configureMessageBody($optionsResolver);

        $this->expectException($expectedExceptionClass);
        $this->expectExceptionMessageMatches($expectedExceptionMessageRegEx);

        $optionsResolver->resolve($body);
    }

    public function testGetNameIsNotEmpty(): void
    {
        self::assertNotEmpty($this->getTopic()::getName(), 'Topic name must not be empty');
    }

    public function testGetDescriptionIsNotEmpty(): void
    {
        self::assertNotEmpty($this->getTopic()::getDescription(), 'Topic description must not be empty');
    }

    /**
     * @dataProvider getDefaultPriorityIsValidDataProvider
     */
    public function testGetDefaultPriorityIsValid(string $queueName): void
    {
        self::assertContains(
            $this->getTopic()->getDefaultPriority($queueName),
            array_keys(MessagePriority::$map),
            sprintf('Topic default priority must be the one of %s constants', MessagePriority::class)
        );
    }

    public function getDefaultPriorityIsValidDataProvider(): array
    {
        return [['queueName' => Config::DEFAULT_QUEUE_NAME]];
    }

    /**
     * @dataProvider validBodyDataProvider
     */
    public function testConfigureMessageBodyWhenValid(array $body, array $expectedBody): void
    {
        $this->assertBodyValid($expectedBody, $body);
    }

    public function validBodyDataProvider(): array
    {
        return [];
    }

    /**
     * @dataProvider invalidBodyDataProvider
     */
    public function testConfigureMessageBodyWhenInvalid(
        array $body,
        string $exceptionClass,
        string $exceptionMessage
    ): void {
        $this->assertBodyInvalid($body, $exceptionClass, $exceptionMessage);
    }

    public function invalidBodyDataProvider(): array
    {
        return [];
    }
}
