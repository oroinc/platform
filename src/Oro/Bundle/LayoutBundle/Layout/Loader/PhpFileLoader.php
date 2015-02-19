<?php

namespace Oro\Bundle\LayoutBundle\Layout\Loader;

use Oro\Component\Layout\CallbackLayoutUpdate;
use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\LayoutManipulatorInterface;

/**
 * Evaluates given PHP file resource, context of the file will consist with variables
 * LayoutManipulatorInterface $layoutManipulator and LayoutItemInterface $item
 * Example:
 *     <?php
 *         // @var LayoutManipulatorInterface $layoutManipulator
 *         // @var LayoutItemInterface $item
 *
 *         $layoutManipulator->add('menu', 'content', 'knp_menu');
 *
 * @see src/Oro/Bundle/LayoutBundle/Tests/Unit/Stubs/Updates/layout_update.php
 */
class PhpFileLoader implements FileLoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load($resource)
    {
        return new CallbackLayoutUpdate(
            function (LayoutManipulatorInterface $layoutManipulator, LayoutItemInterface $item) use ($resource) {
                include $resource;
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource)
    {
        return is_string($resource) && 'php' === pathinfo($resource, PATHINFO_EXTENSION);
    }
}
