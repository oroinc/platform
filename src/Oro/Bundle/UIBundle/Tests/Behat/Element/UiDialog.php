<?php

namespace Oro\Bundle\UIBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

/**
 * This class provides the ability to manage UI dialog
 */
class UiDialog extends Element
{
    public function close()
    {
        $close = $this->spin(function () {
            return $this->findFirstVisibleChild('css', '.ui-dialog-titlebar-close');
        }, 5);

        if ($close) {
            $close->click();
        }
    }

    /**
     * Find first visible child element
     *
     * @param string $selector selector engine name
     * @param string|array $locator selector locator
     *
     * @return NodeElement|null
     */
    protected function findFirstVisibleChild($selector, $locator)
    {
        $visibleElements = array_filter(
            $this->findAll($selector, $locator),
            function (NodeElement $element) {
                return $element->isVisible();
            }
        );

        return array_shift($visibleElements);
    }
}
