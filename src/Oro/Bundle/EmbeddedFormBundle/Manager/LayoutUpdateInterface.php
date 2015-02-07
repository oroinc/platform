<?php

namespace Oro\Bundle\EmbeddedFormBundle\Manager;

use Oro\Component\Layout\LayoutManipulatorInterface;

/**
 * @deprecated this is temporary interface and it must be removed after BAP-7361 finished
 */
interface LayoutUpdateInterface
{
    /**
     * @param LayoutManipulatorInterface $layoutManipulator
     */
    public function updateLayout(LayoutManipulatorInterface $layoutManipulator);
}
