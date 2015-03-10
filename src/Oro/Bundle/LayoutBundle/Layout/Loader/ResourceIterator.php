<?php

namespace Oro\Bundle\LayoutBundle\Layout\Loader;

class ResourceIterator extends \FilterIterator
{
    const PATH_DELIMITER = '/';

    /** @var ResourceFactoryInterface */
    protected $factory;

    /** @var ResourceMatcher|null */
    protected $matcher;

    /**
     * @param ResourceFactoryInterface $factory
     * @param array                    $resources
     */
    public function __construct(ResourceFactoryInterface $factory, array $resources)
    {
        $this->factory = $factory;
        parent::__construct(
            new \RecursiveIteratorIterator(
                new \RecursiveArrayIterator($resources),
                \RecursiveIteratorIterator::LEAVES_ONLY
            )
        );
    }

    /**
     * @param ResourceMatcher $matcher
     */
    public function setMatcher(ResourceMatcher $matcher = null)
    {
        $this->matcher = $matcher;

        // result may be different so just seek to the begging
        $this->rewind();
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->factory->create($this->getPath(), parent::current());
    }

    /**
     * {@inheritdoc}
     */
    public function accept()
    {
        return $this->matcher ? $this->matcher->match($this->getPath(), $this->getInnerIterator()->current()) : true;
    }

    /**
     * Returns current nesting path, exclude index on current level
     *
     * Example:
     *      [
     *          base => [
     *              some_key => [
     *                  some_item1
     *                  some_item2
     *              ]
     *          ]
     *      ]
     *
     * for some item1 will be returned array: base, some_key
     *
     * @return array
     */
    protected function getPath()
    {
        /** @var \RecursiveIteratorIterator $iterator */
        $iterator = $this->getInnerIterator();

        $path = [];
        for ($i = 0, $z = $iterator->getDepth(); $i < $z; $i++) {
            $path[] = $iterator->getSubIterator($i)->key();
        }

        return $path;
    }
}
