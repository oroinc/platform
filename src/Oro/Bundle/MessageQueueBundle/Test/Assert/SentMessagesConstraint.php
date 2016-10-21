<?php

namespace Oro\Bundle\MessageQueueBundle\Test\Assert;

/**
 * Constraint that checks if exactly given messages were sent.
 */
class SentMessagesConstraint extends \PHPUnit_Framework_Constraint
{
    /**
     * @var array [['topic' => topic name, 'message' => message], ...]
     */
    protected $messages;

    /**
     * @param array $messages [['topic' => topic name, 'message' => message], ...]
     */
    public function __construct(array $messages)
    {
        parent::__construct();
        $this->messages = $messages;
    }

    /**
     * {@inheritdoc}
     */
    public function evaluate($other, $description = '', $returnResult = false)
    {
        $comparatorFactory = \SebastianBergmann\Comparator\Factory::getInstance();
        try {
            $comparator = $comparatorFactory->getComparatorFor($this->messages, $other);
            $comparator->assertEquals($this->messages, $other);
        } catch (\SebastianBergmann\Comparator\ComparisonFailure $f) {
            if ($returnResult) {
                return false;
            }

            throw new \PHPUnit_Framework_ExpectationFailedException(
                trim($description . "\n" . 'Failed asserting that exactly all messages were sent.'),
                $f
            );
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function toString()
    {
        return 'messages ' . $this->exporter->export($this->messages);
    }
}
