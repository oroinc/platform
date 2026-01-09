<?php

namespace Oro\Component\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ContainerAwareInterface should be implemented by classes that depends on a Container.
 *
 * This is a compatibility interface that provides the same functionality as the deprecated
 * Symfony\Component\DependencyInjection\ContainerAwareInterface.
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
