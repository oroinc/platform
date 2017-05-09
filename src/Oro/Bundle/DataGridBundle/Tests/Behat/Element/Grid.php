<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use WebDriver\Exception\ElementNotVisible;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Table;

/**
 * @method GridRow getRowByNumber($rowNumber) @see Table::getRowByNumber($rowNumber)
 * @method GridRow getRowByContent($content) @see Table::getRowByContent($content)
 * @method GridRow[] getRows() @see Table::getRows()
 */
class Grid extends Table
{
    const TABLE_HEADER_ELEMENT = 'GridHeader';
    const TABLE_ROW_ELEMENT = 'GridRow';
    const ERROR_NO_ROW = "Can't get %s row, because there are only %s rows in grid";
    const ERROR_NO_ROW_CONTENT = 'Grid has no record with "%s" content';

    /**
     * @param string $title
     * @throws \Exception
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

    public function clickViewList()
    {
        $list = $this->getViewList();

        self::assertTrue($list->isValid(), 'Grid view list not found on the page');
        $list->press();
    }

    /**
     * @param int $number
     * @param int $cellNumber
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
     * @param int $number
     * @param int $cellNumber
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
     * @param string $content
     */
    public function checkRecord($content)
    {
        $this->getRowByContent($content)->checkMassActionCheckbox();
    }

    /**
     * @param string $content
     */
    public function uncheckRecord($content)
    {
        $this->getRowByContent($content)->uncheckMassActionCheckbox();
    }

    /**
     * @return NodeElement
     * @throws \Exception
     */
    public function getMassActionButton()
    {
        $massActionsButton = $this->findButton('Mass Actions');

        if (!$massActionsButton || !$massActionsButton->isVisible()) {
            throw ElementNotVisible::factory(
                ElementNotVisible::ELEMENT_NOT_VISIBLE,
                'Mass Action dropdown is not prsent or not visible on page'
            );
        }

        return $massActionsButton;
    }

    /**
     * @param string $title
     * @return NodeElement|null
     */
    public function getMassActionLink($title)
    {
        return $this->elementFactory->createElement('GridFloatingMenu')->findLink($title);
    }

    /**
     * @param string $title
     * @throws \Exception
     */
    public function massCheck($title)
    {
        $this->elementFactory->createElement('MassActionHeadCheckbox')->click();
        $this->elementFactory->createElement('GridFloatingMenu')->clickLink($title);
    }

    /**
     * @param int $number
     * @throws ElementNotFoundException
     */
    public function selectPageSize($number)
    {
        $pageSizeElement = $this->elementFactory->createElement('PageSize');
        $pageSizeElement->find('css', '.btn')->click();
        $pageSizeElement->clickLink($number);
    }

    /**
     * @param string $content
     * @param string $action
     */
    public function clickActionLink($content, $action)
    {
        $row = $this->getRowByContent($content);
        $link = $row->getActionLink($action);
        $link->click();
    }

    /**
     * @return NodeElement
     */
    public function getViewList()
    {
        return $this->getElement('GridViewList');
    }
}
