<?php

namespace Oro\Bundle\MessageQueueBundle\Test\Assert;

use Oro\Component\MessageQueue\Client\Message;

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
    private $canonicalize;

    /**
     * @param array $message ['topic' => topic name, 'message' => message] or ['topic' => topic name]
     * @param bool $isSubJobMessage
     * @param bool $canonicalize Arrays with integer keys and scalar values are sorted before comparison
     */
    public function __construct(array $message, bool $isSubJobMessage = false, bool $canonicalize = false)
    {
        $this->message = $message;
        $this->isSubJobMessage = $isSubJobMessage;
        $this->canonicalize = $canonicalize;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function matches($other): bool
    {
        if (!is_array($other)) {
            return false;
        }

        if (array_key_exists('message', $this->message)) {
            $constraint = new \PHPUnit\Framework\Constraint\IsEqual($this->canonicalizeRecursive($this->message));
            foreach ($other as $message) {
                if ($message['message'] instanceof Message) {
                    $message['message'] = $message['message']->getBody();
                }

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
    public function toString(): string
    {
        return 'the message ' . $this->exporter()->export($this->message) . ' was sent';
    }

    /**
     * {@inheritdoc}
     */
    protected function failureDescription($other): string
    {
        return $this->toString();
    }

    /**
     * {@inheritdoc}
     */
    protected function additionalFailureDescription($other): string
    {
        return 'All sent messages: ' . $this->exporter()->export($other);
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
