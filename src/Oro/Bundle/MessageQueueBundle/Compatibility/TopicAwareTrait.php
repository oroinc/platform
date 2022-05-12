<?php

namespace Oro\Bundle\MessageQueueBundle\Compatibility;

use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * This trait introduces simplified way to get resolved message body based on MQ Topic services introduced in 5.0
 */
trait TopicAwareTrait
{
    /**
     * @var TopicInterface
     */
    protected $topic;

    public function setTopic(TopicInterface $topic): void
    {
        $this->topic = $topic;
    }

    protected function getResolvedBody(
        MessageInterface $message,
        ?LoggerInterface $logger = null
    ): ?array {
        if (!$this->topic) {
            throw new \InvalidArgumentException('Required topic dependency was not added');
        }

        try {
            $body = JSON::decode($message->getBody());
            $resolver = new OptionsResolver();
            $this->topic->configureMessageBody($resolver);

            return $resolver->resolve($body);
        } catch (ExceptionInterface $e) {
            if ($logger) {
                $logger->critical(
                    'Got invalid message.',
                    [
                        'topic' => $this->topic::getName(),
                        'message' => $message->getBody(),
                        'exception' => $e
                    ]
                );
            }

            return null;
        }
    }
}
