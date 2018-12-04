<?php

namespace Oro\Bundle\UIBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class ContextSelector extends Element
{
    /**
     * Select context in popup entity selector
     *
     * @param $needle
     */
    public function select($needle)
    {
        $this->find('css', '.dropdown-toggle')->click();
        $contexts = $this->findAll('css', 'ul.context-items-dropdown li');

        /** @var NodeElement $context */
        foreach ($contexts as $context) {
            if ($needle === $context->getText()) {
                $context->click();
                $this->getDriver()->waitForAjax();

                return;
            }
        }

        self::fail(sprintf('Can\'t find "%s" context in context selector', $needle));
    }
}
