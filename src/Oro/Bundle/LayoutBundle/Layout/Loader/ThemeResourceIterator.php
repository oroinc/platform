<?php

namespace Oro\Bundle\LayoutBundle\Layout\Loader;

class ThemeResourceIterator extends \RecursiveIteratorIterator
{
    /** @var ResourceFactoryInterface */
    protected $factory;

    /**
     * @param ResourceFactoryInterface $factory
     * @param array                    $themeResources
     */
    public function __construct(ResourceFactoryInterface $factory, array $themeResources)
    {
        $this->factory = $factory;

        parent::__construct(new \RecursiveArrayIterator($themeResources), \RecursiveIteratorIterator::LEAVES_ONLY);
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        $path = [];
        for ($i = 0, $z = $this->getDepth(); $i <= $z; $i++) {
            $path[] = $this->getSubIterator($i)->key();
        }

        $path = implode(ResourceFactoryInterface::PATH_DELIMITER, $path);

        return $this->factory->create($path, parent::current());
    }
}
