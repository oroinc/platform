<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Step\Stub;

use Oro\Bundle\BatchBundle\Exception\InvalidItemException;
use Oro\Bundle\BatchBundle\Item\ItemProcessorInterface;
use Oro\Bundle\BatchBundle\Item\Support\ClosableInterface;

class Processor implements ItemProcessorInterface, ClosableInterface
{
    public const INVALID_ITEM = 'invalid_processor_item';
    public const INVALID_ITEM_EXCEPTION_MESSAGE = 'Processor exception message';
    public const LOGIC_EXCEPTION_MESSAGE = 'Processor logic exception message';

    /**
     * {@inheritdoc}
     *
     * @throws InvalidItemException
     */
    public function process($item)
    {
        if ($item === self::INVALID_ITEM) {
            throw new InvalidItemException(
                self::INVALID_ITEM_EXCEPTION_MESSAGE,
                [$item],
                ['parameters' => ['option']]
            );
        }

        return $item;
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        throw new \LogicException(self::LOGIC_EXCEPTION_MESSAGE);
    }
}
