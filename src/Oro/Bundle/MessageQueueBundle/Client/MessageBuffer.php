<?php

namespace Oro\Bundle\MessageQueueBundle\Client;

use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessageBuilderInterface;

/**
 * Implements a buffer for messages to be sent to the queue.
 */
class MessageBuffer
{
    /** @var array [message id => [topic, message], ...] */
    private $buffer = [];

    /** @var array [topic => [message id, ...], ...] */
    private $topics = [];

    /** @var bool */
    private $resolved = false;

    /**
     * Gets the list of topics for all messages the buffer contains.
     *
     * @return string[]
     */
    public function getTopics(): array
    {
        return array_keys($this->topics);
    }

    /**
     * Checks whether the buffer contains at least one message.
     */
    public function hasMessages(): bool
    {
        return !empty($this->buffer);
    }

    /**
     * Gets all messages the buffer contains.
     *
     * @return array [message id => [topic, message (can be string|array|Message)], ...]
     */
    public function getMessages(): array
    {
        if (!$this->resolved) {
            foreach ($this->buffer as $messageId => $item) {
                $message = $item[1];
                if ($message instanceof MessageBuilderInterface) {
                    $this->buffer[$messageId][1] = $message->getMessage();
                }
            }
            $this->resolved = true;
        }

        return $this->buffer;
    }

    /**
     * Gets a message by its identifier from the buffer.
     *
     * @param int $messageId
     *
     * @return string|array|Message|null A message or NULL if the buffer does not contain a message with the given ID
     */
    public function getMessage(int $messageId)
    {
        $messages = $this->getMessages();
        if (!isset($messages[$messageId])) {
            return null;
        }

        return $messages[$messageId][1];
    }

    /**
     * Checks whether the buffer contains at least one message for the given topic.
     */
    public function hasMessagesForTopic(string $topic): bool
    {
        return !empty($this->topics[$topic]);
    }

    /**
     * Gets all messages for the given topic.
     *
     * @param string $topic
     *
     * @return iterable [message id => message (can be string|array|Message), ...]
     */
    public function getMessagesForTopic(string $topic): iterable
    {
        if (!empty($this->topics[$topic])) {
            $messageIds = $this->topics[$topic];
            $messages = $this->getMessages();
            foreach ($messageIds as $messageId) {
                if (isset($messages[$messageId])) {
                    yield $messageId => $messages[$messageId][1];
                }
            }
        }
    }

    /**
     * Checks whether the buffer contains at least one message for any of given topics.
     *
     * @param string[] $topics
     *
     * @return bool
     */
    public function hasMessagesForTopics(array $topics): bool
    {
        foreach ($topics as $topic) {
            if (!empty($this->topics[$topic])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets all messages for all given topics.
     *
     * @param string[] $topics
     *
     * @return iterable [message id => [topic, message (can be string|array|Message)], ...]
     */
    public function getMessagesForTopics(array $topics): iterable
    {
        foreach ($topics as $topic) {
            if (!empty($this->topics[$topic])) {
                $messageIds = $this->topics[$topic];
                $messages = $this->getMessages();
                foreach ($messageIds as $messageId) {
                    if (isset($messages[$messageId])) {
                        yield $messageId => $messages[$messageId];
                    }
                }
            }
        }
    }

    /**
     * Adds a message to the buffer.
     *
     * @param string                                       $topic
     * @param string|array|Message|MessageBuilderInterface $message
     */
    public function addMessage(string $topic, $message): void
    {
        if ($this->resolved && $message instanceof MessageBuilderInterface) {
            $message = $message->getMessage();
        }
        $this->buffer[] = [$topic, $message];
        $messageId = count($this->buffer) - 1;
        $this->topics[$topic][$messageId] = $messageId;
    }

    /**
     * Removes a message from the buffer.
     */
    public function removeMessage(int $messageId): void
    {
        if (!isset($this->buffer[$messageId])) {
            return;
        }

        $topic = $this->buffer[$messageId][0];
        unset($this->buffer[$messageId], $this->topics[$topic][$messageId]);
        if (empty($this->topics[$topic])) {
            unset($this->topics[$topic]);
        }
    }

    /**
     * Replaces a message in the buffer with the given message.
     * The topic of the new message keeps the same as the topic of the replaced message.
     *
     * @param int                                          $messageId
     * @param string|array|Message|MessageBuilderInterface $message
     *
     * @throws \LogicException if the buffer does not contain a message with the given ID
     */
    public function replaceMessage(int $messageId, $message): void
    {
        if (!isset($this->buffer[$messageId])) {
            throw new \LogicException(sprintf(
                'The buffer does contain a message with the identifier equals to %d.',
                $messageId
            ));
        }

        if ($this->resolved && $message instanceof MessageBuilderInterface) {
            $message = $message->getMessage();
        }
        $this->buffer[$messageId][1] = $message;
    }

    /**
     * Deletes all messages from the buffer.
     */
    public function clear(): void
    {
        $this->buffer = [];
        $this->topics = [];
        $this->resolved = false;
    }
}
