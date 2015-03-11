<?php

namespace Oro\Bundle\LayoutBundle\Layout\Loader;

class ResourceIterator extends \IteratorIterator
{
    /** @var ResourceFactoryInterface */
    protected $factory;

    /** @var ChainPathProvider|null */
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
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->factory->create(parent::current());
    }
}
