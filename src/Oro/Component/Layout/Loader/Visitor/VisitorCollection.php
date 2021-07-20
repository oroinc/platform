<?php

namespace Oro\Component\Layout\Loader\Visitor;

use Oro\Component\Layout\Exception\UnexpectedTypeException;

class VisitorCollection extends \ArrayIterator
{
    public function __construct(array $conditions = [])
    {
        $this->validate($conditions);

        parent::__construct($conditions);
    }

    /**
     * {@inheritdoc}
     */
    public function append($condition)
    {
        $this->validate([$condition]);

        parent::append($condition);
    }

    protected function validate(array $conditions)
    {
        foreach ($conditions as $condition) {
            if (!$condition instanceof VisitorInterface) {
                throw new UnexpectedTypeException(
                    $condition,
                    'Oro\Component\Layout\Loader\Visitor\VisitorInterface'
                );
            }
        }
    }
}
