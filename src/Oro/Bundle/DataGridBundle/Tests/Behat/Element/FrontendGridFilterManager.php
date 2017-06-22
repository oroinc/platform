<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class FrontendGridFilterManager extends Element
{
    /**
     * @param string $title
     */
    public function checkColumnFilter($title)
    {
        $this->ensureManagerVisible();

        $filterItem = $this->getFilterCheckbox($title);

        if ($filterItem->isChecked()) {
            return;
        }

        $filterItem->click();

        self::assertTrue(
            $filterItem->isChecked(),
            'Can not check filter item ' . $title
        );
    }

    /**
     * @param string $title
     */
    public function uncheckColumnFilter($title)
    {
        $this->ensureManagerVisible();

        $visibilityCheckbox = $this->getFilterCheckbox($title);

        if (!$visibilityCheckbox->isChecked()) {
            return;
        }

        $visibilityCheckbox->click();

        self::assertFalse(
            $visibilityCheckbox->isChecked(),
            'Can not uncheck filter item ' . $title
        );
    }

    public function open()
    {
        $this->ensureManagerVisible();
    }

    public function close()
    {
        if (!$this->isVisible()) {
            return;
        }

        $close = $this->find('css', 'span.close');
        $close->click();
    }

    protected function ensureManagerVisible()
    {
        if ($this->isVisible()) {
            return;
        }
        $button = $this->elementFactory->createElement('FrontendGridFilterManagerButton');
        $button->click();

        self::assertTrue($this->isVisible(), 'Can not open grid filter manager dropdown');
    }

    /**
     * @param string $title
     * @return NodeElement|mixed|null
     */
    protected function getFilterCheckbox($title)
    {
        $filterCheckbox = $this->find(
            'css',
            'li.datagrid-manager__checkboxes-item label[title="' . $title . '"] input[type=checkbox]'
        );

        self::assertNotNull($filterCheckbox, 'Can not find filter: ' . $title);

        return $filterCheckbox;
    }
}
