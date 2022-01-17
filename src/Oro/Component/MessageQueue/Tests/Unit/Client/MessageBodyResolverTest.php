<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client;

use Oro\Component\MessageQueue\Client\MessageBodyResolver;
use Oro\Component\MessageQueue\Consumption\Exception\InvalidMessageBodyException;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Oro\Component\MessageQueue\Topic\TopicRegistry;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MessageBodyResolverTest extends \PHPUnit\Framework\TestCase
{
    private const TOPIC_NAME = 'sample.topic';

    private MessageBodyResolver $resolver;

    private TopicInterface|\PHPUnit\Framework\MockObject\MockObject $topic;

    protected function setUp(): void
    {
        $topicRegistry = $this->createMock(TopicRegistry::class);

        $this->topic = $this->createMock(TopicInterface::class);
        $topicRegistry
            ->expects(self::once())
            ->method('get')
            ->with(self::TOPIC_NAME)
            ->willReturn($this->topic);

        $this->resolver = new MessageBodyResolver($topicRegistry);
    }

    public function testResolveBodyReturnsUnchangedWhenNotScalarAndNoDefinedOptions(): void
    {
        $body = ['sample_key' => 'sample_value'];

        self::assertSame($body, $this->resolver->resolveBody(self::TOPIC_NAME, $body));
    }

    /**
     * @dataProvider resolveBodyReturnsNormalizedWhenScalarAndNoDefinedOptionsDataProvider
     *
     * @param string|float|int|bool|null $body
     * @param string $expected
     */
    public function testResolveBodyReturnsNormalizedWhenScalarAndNoDefinedOptions(
        string|float|int|bool|null $body,
        string $expected
    ): void {
        self::assertSame($expected, $this->resolver->resolveBody(self::TOPIC_NAME, $body));
    }

    public function resolveBodyReturnsNormalizedWhenScalarAndNoDefinedOptionsDataProvider(): array
    {
        return [
            'empty string' => [
                'body' => '',
                'expected' => '',
            ],
            'float' => [
                'body' => 42.42,
                'expected' => '42.42',
            ],
            'int' => [
                'body' => 42,
                'expected' => '42',
            ],
            'bool true' => [
                'body' => true,
                'expected' => '1',
            ],
            'bool false' => [
                'body' => false,
                'expected' => '',
            ],
            'null' => [
                'body' => null,
                'expected' => '',
            ],
        ];
    }

    public function testResolveBodyWhenScalarAndHasDefinedOptions(): void
    {
        $this->topic
            ->expects(self::once())
            ->method('configureMessageBody')
            ->willReturnCallback(static function (OptionsResolver $optionsResolver) {
                $optionsResolver
                    ->setDefined('body')
                    ->setNormalizer('body', static fn (Options $options, $value) => $value * 2);
            });

        self::assertSame('42', $this->resolver->resolveBody(self::TOPIC_NAME, 21));
    }

    public function testResolveBodyWhenScalarButNormalizesToArray(): void
    {
        $this->topic
            ->expects(self::once())
            ->method('configureMessageBody')
            ->willReturnCallback(static function (OptionsResolver $optionsResolver) {
                $optionsResolver
                    ->setDefined('body')
                    ->setNormalizer('body', static fn (Options $options, $value) => ['sum' => $value * 2]);
            });

        self::assertSame(['sum' => 42], $this->resolver->resolveBody(self::TOPIC_NAME, 21));
    }

    public function testResolveBodyWhenScalarButNormalizesToNull(): void
    {
        $this->topic
            ->expects(self::once())
            ->method('configureMessageBody')
            ->willReturnCallback(static function (OptionsResolver $optionsResolver) {
                $optionsResolver
                    ->setDefined('body')
                    ->setNormalizer('body', static fn (Options $options, $value) => null);
            });

        self::assertSame('', $this->resolver->resolveBody(self::TOPIC_NAME, 21));
    }

    public function testResolveBodyWhenScalarAndHasDefinedOptionsAndInvalid(): void
    {
        $this->topic
            ->expects(self::once())
            ->method('configureMessageBody')
            ->willReturnCallback(static function (OptionsResolver $optionsResolver) {
                $optionsResolver
                    ->setDefined('body')
                    ->setAllowedValues('body', static fn ($value) => $value < 21);
            });

        $this->expectException(InvalidMessageBodyException::class);

        $this->resolver->resolveBody(self::TOPIC_NAME, 21);
    }

    public function testResolveBodyWhenNotScalarAndHasDefinedOptions(): void
    {
        $this->topic
            ->expects(self::once())
            ->method('configureMessageBody')
            ->willReturnCallback(static function (OptionsResolver $optionsResolver) {
                $optionsResolver->setDefault('sample_key', 'sample_value');
            });

        self::assertSame(['sample_key' => 'sample_value'], $this->resolver->resolveBody(self::TOPIC_NAME, []));
    }

    public function testResolveBodyWhenNotScalarAndHasDefinedOptionsAndInvalid(): void
    {
        $this->topic
            ->expects(self::once())
            ->method('configureMessageBody')
            ->willReturnCallback(static function (OptionsResolver $optionsResolver) {
                $optionsResolver->setDefined('sample_key');
            });

        $this->expectException(InvalidMessageBodyException::class);

        $this->resolver->resolveBody(self::TOPIC_NAME, ['undefined_option' => 'sample_value']);
    }
}
