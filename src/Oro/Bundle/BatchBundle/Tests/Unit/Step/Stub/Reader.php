<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Step\Stub;

use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use Akeneo\Bundle\BatchBundle\Item\ItemReaderInterface;
use Oro\Bundle\BatchBundle\Item\Support\ClosableInterface;

class Reader implements ItemReaderInterface, ClosableInterface
{
    public const INVALID_ITEM = 'invalid_reader_item';
    public const INVALID_ITEM_EXCEPTION_MESSAGE = 'Reader exception message';
    public const LOGIC_EXCEPTION_MESSAGE = 'Reader logic exception message';

    /**
     * @var \ArrayIterator
     */
    private $iterator;

    /**
     * @param array|string[] $items
     */
    public function __construct(array $items = [])
    {
        $this->iterator = new \ArrayIterator($items);
    }

    /**
     * @throws InvalidItemException
     * @return mixed|null
     */
    public function read()
    {
        if ($this->iterator->valid()) {
            $item = $this->iterator->current();
            $this->iterator->next();

            if ($item === self::INVALID_ITEM) {
                throw new InvalidItemException(
                    self::INVALID_ITEM_EXCEPTION_MESSAGE,
                    [$item],
                    ['parameters' => ['option']]
                );
            }

            return $item;
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function close(): void
    {
        throw new \LogicException(self::LOGIC_EXCEPTION_MESSAGE);
    }
}
