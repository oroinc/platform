<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Step\Stub;

use Oro\Bundle\BatchBundle\Exception\InvalidItemException;
use Oro\Bundle\BatchBundle\Item\ItemWriterInterface;
use Oro\Bundle\BatchBundle\Item\Support\ClosableInterface;

class Writer implements ItemWriterInterface, ClosableInterface
{
    public const INVALID_ITEM = 'invalid_writer_item';
    public const INVALID_ITEM_EXCEPTION_MESSAGE = 'Writer exception message';
    public const LOGIC_EXCEPTION_MESSAGE = 'Writer logic exception message';

    /**
     * {@inheritdoc}
     *
     * @throws InvalidItemException
     */
    public function write(array $items): void
    {
        foreach ($items as $item) {
            if ($item === self::INVALID_ITEM) {
                throw new InvalidItemException(
                    self::INVALID_ITEM_EXCEPTION_MESSAGE,
                    [$item],
                    ['parameters' => ['option']]
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        throw new \LogicException(self::LOGIC_EXCEPTION_MESSAGE);
    }
}
