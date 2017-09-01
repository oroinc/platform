<?php

namespace Oro\Bundle\ActionBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class PageActionButtonsContainerElement extends Element
{
    /**
     * @param string $text Filter title
     * @param bool $strict
     *
     * @return Element
     */
    public function getAction($text, $strict = false)
    {
        $pageActions = $this->getPageActions();

        foreach ($pageActions as $item) {
            if ($strict) {
                $found = $item->getText() === $text;
            } else {
                $found = stripos($item->getText(), $text) !== false;
            }

            if ($found) {
                return $item;
            }
        }

        self::fail(sprintf('Can\'t find page action with "%s" label', $text));
    }

    /**
     * @return Element[]
     */
    public function getPageActions()
    {
        return $this->elementFactory->findAllElements('PageAction', $this);
    }
}
