<?php

namespace Oro\Bundle\UIBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class UiDialog extends Element
{
    public function close()
    {
        $this->find('css', '.ui-dialog-titlebar-close')->click();
    }
}
