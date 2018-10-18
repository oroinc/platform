<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;

class GridFilterManager extends FrontendGridFilterManager
{
    /**
     * @param string $title
     * @return NodeElement|mixed|null
     */
    protected function getFilterCheckbox($title)
    {
        $filterCheckbox = $this->find(
            'css',
            'td.visibility-cell[title="' . $title . '"] input[type="checkbox"]'
        );

        self::assertNotNull($filterCheckbox, 'Can not find filter: ' . $title);

        return $filterCheckbox;
    }

    public function close()
    {
        if (!$this->isVisible()) {
            return;
        }

        $button = $this->elementFactory->createElement('GridFilterManagerButton');
        $button->click();
    }
}
