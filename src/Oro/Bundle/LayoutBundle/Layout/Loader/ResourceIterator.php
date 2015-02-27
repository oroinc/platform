<?php

namespace Oro\Bundle\LayoutBundle\Layout\Loader;

class ResourceIterator extends \FilterIterator
{
    const PATH_DELIMITER = '/';

    /** @var ResourceFactoryInterface */
    protected $factory;

    /** @var array */
    protected $filterPath;

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
     * @param null|string $filterPath
     */
    public function setFilterPath($filterPath = null)
    {
        $this->filterPath = null === $filterPath
            ? []
            : (is_array($filterPath) ? $filterPath : explode(self::PATH_DELIMITER, $filterPath));
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
        if (empty($this->filterPath)) {
            return true;
        }

        $currentPath = $this->getPath();
        if (count($currentPath) <= count($this->filterPath)) {
            $equals = true;
            foreach ($currentPath as $k => $v) {
                if ($v !== $this->filterPath[$k]) {
                    $equals = false;

                    break;
                }
            }

            return $equals;
        }

        return false;
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
