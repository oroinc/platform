<?php

namespace Oro\Component\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This is a temporary compatibility trait that provides the same functionality as the
 * `Symfony\Component\DependencyInjection\ContainerAwareTrait` in Symfony 6.
 */
trait ContainerAwareTrait
{
    /**
     * @var ContainerInterface|null
     */
    protected $container;

    /**
     * @return void
     */
    public function setContainer(?ContainerInterface $container = null)
    {
        if (1 > \func_num_args()) {
            trigger_deprecation(
                'oro/platform',
                '7.0',
                'Calling "%s::%s()" without any arguments is deprecated, pass null explicitly instead.',
                __CLASS__,
                __FUNCTION__
            );
        }

        $this->container = $container;
    }
}
