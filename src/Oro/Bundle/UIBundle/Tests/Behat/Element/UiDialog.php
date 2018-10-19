<?php

namespace Oro\Bundle\UIBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

/**
 * This class provides the ability to manage UI dialog
 */
class UiDialog extends Element
{
    public function close()
    {
        $close = $this->spin(function () {
            return $this->findVisible('css', '.ui-dialog-titlebar-close');
        }, 5);

        if ($close) {
            $close->click();
        }
    }
}
