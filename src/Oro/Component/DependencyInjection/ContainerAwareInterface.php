<?php

namespace Oro\Component\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This is a temporary compatibility interface that provides the same functionality as the
 * `Symfony\Component\DependencyInjection\ContainerAwareInterface` in Symfony 6.
 */
interface ContainerAwareInterface
{
    /**
     * Sets the container.
     *
     * @return void
     */
    public function setContainer(?ContainerInterface $container);
}
