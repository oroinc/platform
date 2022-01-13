<?php

namespace Oro\Component\MessageQueue\Test;

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

    /**
     * @dataProvider validBodyDataProvider
     *
     * @param array $body
     * @param array $expectedBody
     */
    public function testConfigureMessageBodyWhenValid(array $body, array $expectedBody): void
    {
        $optionsResolver = new OptionsResolver();
        $this->topic->configureMessageBody($optionsResolver);

        self::assertEquals($expectedBody, $optionsResolver->resolve($body));
    }

    abstract public function validBodyDataProvider(): array;

    /**
     * @dataProvider invalidBodyDataProvider
     *
     * @param array $body
     * @param string $exceptionClass
     * @param string $exceptionMessage
     */
    public function testConfigureMessageBodyWhenInvalid(
        array $body,
        string $exceptionClass,
        string $exceptionMessage
    ): void {
        $optionsResolver = new OptionsResolver();
        $this->topic->configureMessageBody($optionsResolver);

        $this->expectException($exceptionClass);
        $this->expectExceptionMessageMatches($exceptionMessage);

        $optionsResolver->resolve($body);
    }

    abstract public function invalidBodyDataProvider(): array;
}
