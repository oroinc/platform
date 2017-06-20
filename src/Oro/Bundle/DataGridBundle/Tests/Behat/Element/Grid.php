<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use WebDriver\Exception\ElementNotVisible;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Table;

/**
 * @method GridRow getRowByNumber($rowNumber) @see Table::getRowByNumber($rowNumber)
 * @method GridRow getRowByContent($content) @see Table::getRowByContent($content)
 * @method GridRow[] getRows() @see Table::getRows()
 */
class Grid extends Table implements GridInterface
{
    const TABLE_HEADER_ELEMENT = 'GridHeader';
    const TABLE_ROW_ELEMENT = 'GridRow';
    const ERROR_NO_ROW = "Can't get %s row, because there are only %s rows in grid";
    const ERROR_NO_ROW_CONTENT = 'Grid has no record with "%s" content';

    /**
     * {@inheritdoc}
     */
    public function getMappedChildElementName($name)
    {
        if (!isset($this->options['mapping'][$name])) {
            return $name;
        }

        return $this->options['mapping'][$name];
    }

    /**
     * {@inheritdoc}
     */
    public function getMassActionLink($title)
    {
        return $this->elementFactory->createElement('GridFloatingMenu')->findLink($title);
    }

    /**
     * {@inheritdoc}
     */
    public function getSelectAllMassActionLink($title)
    {
        return $this->getElement('GridActionsMassActionMenu')->findLink($title);
    }

    /**
     * {@inheritdoc}
     */
    public function clickMassActionLink($title)
    {
        $massActionsButton = $this->getMassActionButton();
        $massActionsButton->press();

        $massActionLink = $this->getMassActionLink($title);
        self::assertNotNull($massActionLink, 'Mass action link not found on the page');
        self::assertTrue($massActionLink->isVisible(), 'Mass action link is not visible');

        $massActionLink->click();
    }

    /**
     * {@inheritdoc}
     */
    public function clickSelectAllMassActionLink($title)
    {
        $massActionsButton = $this->getMassActionButton();
        $massActionsButton->press();

        $massActionLink = $this->getSelectAllMassActionLink($title);
        self::assertNotNull($massActionLink, 'Mass action link not found on the page');
        self::assertTrue($massActionLink->isVisible(), 'Mass action link is not visible');

        $massActionLink->click();
    }

    public function clickViewList()
    {
        $list = $this->getViewList();

        self::assertTrue($list->isValid(), 'Grid view list not found on the page');
        $list->press();
    }

    /**
     * {@inheritdoc}
     */
    public function checkFirstRecords($number, $cellNumber = 0)
    {
        $rows = $this->getRows();

        self::assertGreaterThanOrEqual(
            $number,
            count($rows),
            sprintf('Can\'t check %s records, because grid has only %s records', $number, count($rows))
        );

        for ($i = 0; $i < $number; $i++) {
            $rows[$i]->checkMassActionCheckbox($cellNumber);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function uncheckFirstRecords($number, $cellNumber = 0)
    {
        $rows = $this->getRows();

        self::assertGreaterThanOrEqual(
            $number,
            count($rows),
            sprintf('Can\'t uncheck %s records, because grid has only %s records', $number, count($rows))
        );

        for ($i = 0; $i < $number; $i++) {
            $rows[$i]->uncheckMassActionCheckbox($cellNumber);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function checkRecord($content)
    {
        $this->getRowByContent($content)->checkMassActionCheckbox();
    }

    /**
     * {@inheritdoc}
     */
    public function uncheckRecord($content)
    {
        $this->getRowByContent($content)->uncheckMassActionCheckbox();
    }

    /**
     * {@inheritdoc}
     */
    public function getMassActionButton()
    {
        $massActionsButton = $this->elementFactory->createElement('MassActionButton', $this);

        if (!$massActionsButton || !$massActionsButton->isVisible()) {
            throw ElementNotVisible::factory(
                ElementNotVisible::ELEMENT_NOT_VISIBLE,
                'Mass Action dropdown is not present or not visible on page'
            );
        }

        return $massActionsButton;
    }

    /**
     * {@inheritdoc}
     */
    public function massCheck($title)
    {
        $massActionHeadCheckboxElementName = $this->getMappedChildElementName('MassActionHeadCheckbox');

        $this->elementFactory->createElement($massActionHeadCheckboxElementName, $this)->click();
        $this->elementFactory->createElement('GridFloatingMenu')->clickLink($title);
    }

    /**
     * {@inheritdoc}
     */
    public function selectPageSize($number)
    {
        $pageSizeElement = $this->elementFactory->createElement('PageSize');
        $pageSizeElement->find('css', '.btn')->click();
        $pageSizeElement->clickLink($number);
    }

    /**
     * {@inheritdoc}
     */
    public function clickActionLink($content, $action)
    {
        $row = $this->getRowByContent($content);
        $link = $row->getActionLink($action);
        $link->click();
    }

    /**
     * {@inheritdoc}
     */
    public function getViewList()
    {
        return $this->getElement($this->getMappedChildElementName('GridViewList'));
    }
}
