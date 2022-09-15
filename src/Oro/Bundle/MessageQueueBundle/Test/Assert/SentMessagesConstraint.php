<?php

namespace Oro\Bundle\MessageQueueBundle\Test\Assert;

use Oro\Component\MessageQueue\Client\Message;

/**
 * Constraint that checks if exactly given messages were sent.
 */
class SentMessagesConstraint extends \PHPUnit\Framework\Constraint\Constraint
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
        $this->messages = $messages;
    }

    public function evaluate($other, string $description = '', bool $returnResult = false): ?bool
    {
        $comparatorFactory = \SebastianBergmann\Comparator\Factory::getInstance();
        try {
            $other = array_map(function (array $array) {
                if ($array['message'] instanceof Message) {
                    $array['message'] = $array['message']->getBody();
                }

                return $array;
            }, $other);

            $comparator = $comparatorFactory->getComparatorFor($this->messages, $other);
            $comparator->assertEquals($this->messages, $other);
        } catch (\SebastianBergmann\Comparator\ComparisonFailure $f) {
            if ($returnResult) {
                return false;
            }

            throw new \PHPUnit\Framework\ExpectationFailedException(
                trim($description . "\n" . 'Failed asserting that exactly all messages were sent.'),
                $f
            );
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function toString(): string
    {
        return 'messages ' . $this->exporter()->export($this->messages);
    }
}
