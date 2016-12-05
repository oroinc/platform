<?php

namespace Oro\Bundle\ActionBundle\Button;

use Oro\Bundle\ActionBundle\Extension\ButtonProviderExtensionInterface;

class ButtonsCollection implements \IteratorAggregate, \Countable
{
    /** @var \SplObjectStorage */
    private $buttonsMap;

    /** @var ButtonInterface[]|null - initialized array */
    private $buttonsArray;

    public function __construct()
    {
        $this->buttonsMap = new \SplObjectStorage();
    }

    /**
     * @param ButtonInterface $button
     * @param ButtonProviderExtensionInterface $extension
     *
     * @return $this
     */
    protected function addButton(ButtonInterface $button, ButtonProviderExtensionInterface $extension)
    {
        //map modified so initialized array should be cleared
        $this->buttonsArray = null;

        $this->buttonsMap->attach($button, $extension);

        return $this;
    }

    /**
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
     * @param ButtonSearchContext $searchContext
     *
     * @return $this
     */
    public function filterAvailable(ButtonSearchContext $searchContext)
    {
        $collection = new static;
        /** @var ButtonInterface $button */
        foreach ($this->buttonsMap as $button) {
            /**@var ButtonProviderExtensionInterface $extension */
            $extension = $this->buttonsMap[$button];

            if ($extension->isAvailable($button, $searchContext)) {
                $collection->addButton($button, $extension);
            }
        }

        return $collection;
    }

    /**
     * @param callable $callable
     * @return $this
     */
    public function map(callable $callable)
    {
        $collection = new static();

        /**@var ButtonInterface $button */
        foreach ($this->buttonsMap as $button) {
            /** @var ButtonProviderExtensionInterface $extension */
            $extension = $this->buttonsMap[$button];
            $mappedButton = call_user_func($callable, $button, $extension);
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
     * @return ButtonInterface[] - sorted by order array of buttins
     */
    public function toArray()
    {
        //if array is already initialized - return it
        if ($this->buttonsArray !== null) {
            return $this->buttonsArray;
        }

        $this->buttonsArray = [];

        foreach ($this->buttonsMap as $button) {
            $this->buttonsArray[] = $button;
        }

        usort($this->buttonsArray, function (ButtonInterface $b1, ButtonInterface $b2) {
            return $b1->getOrder() - $b2->getOrder();
        });

        return $this->buttonsArray;
    }

    /**
     * Returns a string representation of this object.
     *
     * @return string
     */
    public function __toString()
    {
        return __CLASS__ . '@' . spl_object_hash($this);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->toArray());
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->toArray());
    }
}
