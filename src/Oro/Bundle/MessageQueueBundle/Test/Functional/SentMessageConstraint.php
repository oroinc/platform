<?php

namespace Oro\Bundle\MessageQueueBundle\Test\Functional;

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
     * @param array $message ['topic' => topic name, 'message' => message]
     */
    public function __construct(array $message)
    {
        parent::__construct();
        $this->message = $message;
    }

    /**
     * {@inheritdoc}
     */
    protected function matches($other)
    {
        if (!is_array($other)) {
            return false;
        }

        $constraint = new \PHPUnit_Framework_Constraint_IsEqual($this->message);
        foreach ($other as $message) {
            if ($constraint->evaluate($message, '', true)) {
                return true;
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
