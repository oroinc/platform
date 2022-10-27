<?php

namespace Oro\Bundle\ActionBundle\Button;

use Oro\Bundle\ActionBundle\Exception\ButtonCollectionMapException;
use Oro\Bundle\ActionBundle\Extension\ButtonProviderExtensionInterface;

/**
 * The list of ordered actions buttons
 */
class ButtonsCollection implements \IteratorAggregate, \Countable
{
    /** @var \SplObjectStorage (ButtonInterface -> ButtonProviderExtensionInterface) */
    private $buttonsMap;

    /** @var ButtonInterface[]|null - initialized array */
    private $buttonsList;

    public function __construct()
    {
        $this->buttonsMap = new \SplObjectStorage();
    }

    protected function addButton(ButtonInterface $button, ButtonProviderExtensionInterface $extension)
    {
        //map modified so initialized list should be cleared
        $this->buttonsList = null;

        $this->buttonsMap->attach($button, $extension);
    }

    /**
     * Maps all matches of buttons by ButtonSearchContext in ButtonProviderExtensionInterface into the storage.
     * @param ButtonProviderExtensionInterface $extension
     * @param ButtonSearchContext $searchContext
     *
     * @return $this
     */
    public function consume(ButtonProviderExtensionInterface $extension, ButtonSearchContext $searchContext)
    {
        foreach ($extension->find($searchContext) as $button) {
            $this->addButton($button, $extension);
        }

        return $this;
    }

    /**
     * @param callable $filter callable(ButtonInterface $button, ButtonProviderExtensionInterface $extension):bool
     * @return static
     */
    public function filter(callable $filter)
    {
        $collection = new static();

        /** @var ButtonInterface $button */
        foreach ($this->buttonsMap as $button) {
            /** @var ButtonProviderExtensionInterface $extension */
            $extension = $this->buttonsMap[$button];

            if (call_user_func($filter, $button, $extension)) {
                $collection->addButton($button, $extension);
            }
        }

        return $collection;
    }

    /**
     * callable(ButtonInterface $button, ButtonProviderExtensionInterface $extension): ButtonInterface
     * @param callable $map
     * @return static
     */
    public function map(callable $map)
    {
        $collection = new static();

        /**@var ButtonInterface $button */
        foreach ($this->buttonsMap as $button) {
            /** @var ButtonProviderExtensionInterface $extension */
            $extension = $this->buttonsMap[$button];
            $mappedButton = call_user_func($map, $button, $extension);
            if (!$mappedButton instanceof ButtonInterface) {
                throw new ButtonCollectionMapException(
                    sprintf(
                        'Map callback should return `%s` as result got `%s` instead.',
                        ButtonInterface::class,
                        is_object($mappedButton) ? get_class($mappedButton) : gettype($mappedButton)
                    )
                );
            }
            $collection->addButton($mappedButton, $extension);
        }

        return $collection;
    }

    /**
     * @return ButtonInterface[]
     */
    public function toArray()
    {
        return iterator_to_array($this->buttonsMap);
    }

    /**
     * @return ButtonInterface[] - ordered list (numeric array) of buttons
     */
    public function toList()
    {
        //if array is already initialized - return it
        if ($this->buttonsList !== null) {
            return $this->buttonsList;
        }

        $this->buttonsList = $this->toArray();

        usort($this->buttonsList, function (ButtonInterface $b1, ButtonInterface $b2) {
            if ($b1->getOrder() === $b2->getOrder()) {
                # For PHP 7 we returned 0, but for PHP 8, to preserve the order after sort, we should return -1.
                # This is just a workaround that should be replaced with the explicit order for actions.
                return -1;
            }
            return $b1->getOrder() - $b2->getOrder();
        });

        return $this->buttonsList;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->toList());
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return count($this->toArray());
    }
}
