<?php

namespace Oro\Component\MessageQueue\Client;

use Oro\Component\MessageQueue\Consumption\Exception\InvalidMessageBodyException;
use Oro\Component\MessageQueue\Topic\TopicRegistry;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Uses {@see OptionsResolver} to validate and normalize message body according to the specified topic.
 */
class MessageBodyResolver implements MessageBodyResolverInterface
{
    private TopicRegistry $topicRegistry;

    public function __construct(TopicRegistry $topicRegistry)
    {
        $this->topicRegistry = $topicRegistry;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidMessageBodyException
     */
    public function resolveBody(string $topicName, array|string|float|int|bool|null $body): array|string
    {
        $optionsResolver = new OptionsResolver();
        $topic = $this->topicRegistry->get($topicName);
        $topic->configureMessageBody($optionsResolver);

        try {
            $resolvedBody = $this->resolve($optionsResolver, $body);
        } catch (ExceptionInterface $exception) {
            throw InvalidMessageBodyException::create($exception->getMessage(), $topicName, $body, $exception);
        }

        return $resolvedBody;
    }

    /**
     * @param OptionsResolver $optionsResolver
     * @param array|string|float|int|bool|null $body
     *
     * @return array|string Message body resolved with {@see OptionsResolver}
     *
     * @throws ExceptionInterface
     */
    private function resolve(
        OptionsResolver $optionsResolver,
        array|string|float|int|bool|null $body
    ): array|string {
        $isScalar = is_scalar($body) || $body === null;

        if ($isScalar) {
            // Wraps scalar body into an array to make it resolvable via {@see OptionsResolver}.
            $body = ['body' => $body];
        }

        $resolvedBody = $optionsResolver->getDefinedOptions() ? $optionsResolver->resolve($body) : $body;

        if ($isScalar) {
            $resolvedBody = is_array($resolvedBody['body']) ? $resolvedBody['body'] : (string) $resolvedBody['body'];
        }

        return $resolvedBody;
    }
}
