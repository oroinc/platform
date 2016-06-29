<?php

namespace Oro\Component\Layout\Loader\Driver;

use Oro\Component\Layout\Loader\Generator\GeneratorData;

/**
 * Evaluates given PHP file resource, context of the file will consist with variables
 * LayoutManipulatorInterface $layoutManipulator and LayoutItemInterface $item
 *
 * Example:
 *     <?php
 *         // @var \Oro\Component\Layout\LayoutManipulatorInterface $layoutManipulator
 *         // @var \Oro\Component\Layout\LayoutItemInterface $item
 *
 *         $layoutManipulator->add('menu', 'content', 'knp_menu');
 *
 * @see src/Oro/Component/Layout/Tests/Unit/Extension/Theme/Stubs/Updates/layout_update.php
 */
class PhpDriver extends AbstractDriver
{
    /**
     * {@inheritdoc}
     */
    protected function loadResourceGeneratorData($file)
    {
        return new GeneratorData(file_get_contents($file), $file);
    }
}
