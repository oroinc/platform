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
        $this->getDriver()->waitForAjax();

        self::assertTrue(
            $filterItem->isChecked(),
            'Can not check filter item ' . $title
        );
    }

    /**
     * @param string $title
     *
     * @return bool
     */
    public function isCheckColumnFilter($title)
    {
        $this->ensureManagerVisible();

        $filterItem = $this->getFilterCheckbox($title);

        return $filterItem->isChecked();
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

        $this->getDriver()->waitForAjax();

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

        $button = $this->elementFactory->createElement('FrontendGridFilterManagerButton');
        if ($button->isIsset()) {
            $button->clickForce();
        }

        self::assertFalse($this->isVisible(), 'Can not close grid filter manager dropdown');
    }

    public function hasFilter(string $filterTitle): bool
    {
        $this->ensureManagerVisible();

        $filterCheckbox = $this->findFilterCheckbox($filterTitle);
        if (!$filterCheckbox) {
            return false;
        }

        return $filterCheckbox->getParent()->isVisible();
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
        $filterCheckbox = $this->findFilterCheckbox($title);

        self::assertNotNull($filterCheckbox, 'Can not find filter: ' . $title);

        return $filterCheckbox;
    }

    private function findFilterCheckbox(string $title): ?NodeElement
    {
        return $this->find(
            'css',
            '.datagrid-manager__list-item label[title="' . $title . '"] input[type="checkbox"]'
        );
    }
}
