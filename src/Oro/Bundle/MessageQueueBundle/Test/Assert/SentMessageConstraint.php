<?php

namespace Oro\Bundle\MessageQueueBundle\Test\Assert;

/**
 * Constraint that checks if a message was sent.
 */
class SentMessageConstraint extends \PHPUnit\Framework\Constraint\Constraint
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
     * @var bool
     */
    private $canonicalize = false;

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
     * Arrays with integer keys and scalar values are sorted before comparison if set true
     * @param bool $canonicalize
     * @return self
     */
    public function setCanonicalize($canonicalize)
    {
        $this->canonicalize = $canonicalize;
        return $this;
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
            $constraint = new \PHPUnit\Framework\Constraint\IsEqual($this->canonicalizeRecursive($this->message));
            foreach ($other as $message) {
                if ($this->isSubJobMessage && is_array($message['message'])) {
                    if (empty($message['message']['jobId'])) {
                        return false;
                    }
                    unset($message['message']['jobId']);
                }

                if ($constraint->evaluate($this->canonicalizeRecursive($message), '', true)) {
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

    /**
     * @param mixed $message
     * @return mixed
     */
    private function canonicalizeRecursive($message)
    {
        if ($this->canonicalize && is_array($message)) {
            if (is_scalar(reset($message)) && is_int(key($message))) {
                \sort($message);
            } else {
                foreach ($message as &$value) {
                    $value = $this->canonicalizeRecursive($value);
                }
            }
        }

        return $message;
    }
}
