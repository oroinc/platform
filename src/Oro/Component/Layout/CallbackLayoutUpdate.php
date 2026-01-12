<?php

namespace Oro\Component\Layout;

/**
 * Applies layout updates using a callback function.
 *
 * This class wraps a callable that receives a layout manipulator and layout item, allowing custom
 * layout update logic to be executed during the layout building process.
 */
class CallbackLayoutUpdate implements LayoutUpdateInterface
{
    /**
     * The callback used for apply layout updates.
     *
     * @var callable
     */
    private $callback;

    /**
     * @param callable $callback The callback function
     *                           function (LayoutManipulatorInterface $layoutManipulator, LayoutItemInterface $item)
     *
     * @throws Exception\UnexpectedTypeException when the given callback is invalid
     */
    public function __construct($callback)
    {
        if (!is_callable($callback)) {
            throw new Exception\UnexpectedTypeException($callback, 'callable');
        }

        $this->callback = $callback;
    }

    #[\Override]
    public function updateLayout(LayoutManipulatorInterface $layoutManipulator, LayoutItemInterface $item)
    {
        call_user_func($this->callback, $layoutManipulator, $item);
    }
}
