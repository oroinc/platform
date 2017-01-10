<?php

namespace Oro\Bundle\MessageQueueBundle\Test\Assert;

/**
 * Constraint that checks if a message was sent.
 */
class SentMessageConstraint extends \PHPUnit_Framework_Constraint
{
    /**
     * @var array ['topic' => topic name, 'message' => message]
     */
    protected $message;

    /**
     * @var bool
     */
    protected $isSubJobMessage;

    /**
     * @param array $message ['topic' => topic name, 'message' => message] or ['topic' => topic name]
     * @param bool $isSubJobMessage
     */
    public function __construct(array $message, $isSubJobMessage = false)
    {
        parent::__construct();
        $this->message = $message;
        $this->isSubJobMessage = $isSubJobMessage;
    }

    /**
     * {@inheritdoc}
     */
    protected function matches($other)
    {
        if (!is_array($other)) {
            return false;
        }

        if (array_key_exists('message', $this->message)) {
            $constraint = new \PHPUnit_Framework_Constraint_IsEqual($this->message);
            foreach ($other as $message) {
                if ($this->isSubJobMessage && is_array($message['message'])) {
                    if (empty($message['message']['jobId'])) {
                        return false;
                    }
                    unset($message['message']['jobId']);
                }
                if ($constraint->evaluate($message, '', true)) {
                    return true;
                }
            }
        } else {
            foreach ($other as $message) {
                if (is_array($message)
                    && array_key_exists('topic', $message)
                    && $message['topic'] === $this->message['topic']
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function toString()
    {
        return 'the message ' . $this->exporter->export($this->message) . ' was sent';
    }

    /**
     * {@inheritdoc}
     */
    protected function failureDescription($other)
    {
        return $this->toString();
    }

    /**
     * {@inheritdoc}
     */
    protected function additionalFailureDescription($other)
    {
        return 'All sent messages: ' . $this->exporter->export($other);
    }
}
