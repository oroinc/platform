<?php

namespace Oro\Component\Layout\Extension\Theme\Loader;

use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\LayoutManipulatorInterface;
use Oro\Component\Layout\Extension\Theme\Generator\GeneratorData;

/**
 * Evaluates given PHP file resource, context of the file will consist with variables
 * LayoutManipulatorInterface $layoutManipulator and LayoutItemInterface $item
 *
 * Example:
 *     <?php
 *         // @var LayoutManipulatorInterface $layoutManipulator
 *         // @var LayoutItemInterface $item
 *
 *         $layoutManipulator->add('menu', 'content', 'knp_menu');
 *
 * @see src/Oro/Component/Layout/Tests/Unit/Extension/Theme/Stubs/Updates/layout_update.php
 */
class PhpFileLoader extends AbstractLoader
{
    /**
     * {@inheritdoc}
     */
    public function supports($fileName)
    {
        return 'php' === pathinfo($fileName, PATHINFO_EXTENSION);
    }

    /**
     * {@inheritdoc}
     */
    protected function loadResourceGeneratorData($fileName)
    {
        return new GeneratorData(file_get_contents($fileName));
    }
}
