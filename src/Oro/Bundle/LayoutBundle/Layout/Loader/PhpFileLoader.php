<?php

namespace Oro\Bundle\LayoutBundle\Layout\Loader;

use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\LayoutManipulatorInterface;
use Oro\Bundle\LayoutBundle\Layout\Generator\GeneratorData;

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
class PhpFileLoader extends AbstractGeneratorLoader
{
    /**
     * {@inheritdoc}
     */
    public function supports(FileResource $resource)
    {
        return is_string($resource->getFilename()) && 'php' === pathinfo($resource->getFilename(), PATHINFO_EXTENSION);
    }

    /**
     * {@inheritdoc}
     */
    protected function loadResourceGeneratorData(FileResource $resource)
    {
        $filename = $resource->getFilename();

        return new GeneratorData(file_get_contents($filename), $filename);
    }
}
