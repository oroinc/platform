<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Exception\ElementNotFoundException;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use WebDriver\Exception\ElementNotVisible;

class Grid extends Element
{
    /**
     * @param string $title
     * @throws \Exception
     */
    public function clickMassActionLink($title)
    {
        $massActionsButton = $this->getMassActionButton();
        $massActionsButton->press();
        $massActionLink = $this->getMassActionLink($title);
        $massActionLink->click();
    }

    /**
     * @param string $header
     * @param int $rowNumber
     * @return string
     * @throws ExpectationException
     */
    public function getRowValue($header, $rowNumber)
    {
        $columns = $this->getRowByNumber($rowNumber)->findAll('css', 'td');
        $columnNumber = $this->elementFactory->createElement('GridHeader')->getColumnNumber($header);

        return $this->normalizeValueByGuessingType($columns[$columnNumber]->getText());
    }

    /**
     * @param $rowNumber
     * @return NodeElement
     * @throws ExpectationException
     */
    public function getRowByNumber($rowNumber)
    {
        $rowIndex = $rowNumber - 1;
        $rows = $this->getRows();

        if (!isset($rows[$rowIndex])) {
            throw new ExpectationException(
                sprintf('Can\'t get %s row, because there are only %s rows in grid', $rowNumber, count($rows)),
                $this->getDriver()
            );
        }

        return $rows[$rowIndex];
    }

    /**
     * @param int $number
     * @throws ExpectationException
     */
    public function checkFirstRecords($number)
    {
        $rows = $this->getRows();

        if (count($rows) < $number) {
            throw new ExpectationException(
                sprintf(
                    'Can\'t check %s records, because grid has only %s records',
                    $number,
                    count($rows)
                ),
                $this->session->getDriver()
            );
        }

        for ($i = 0; $i < $number; $i++) {
            /** @var NodeElement $row */
            $row = $rows[$i];
            $this->checkRowCheckbox($row);
        }
    }

    /**
     * @param string $content
     * @throws ExpectationException
     */
    public function checkRecord($content)
    {
        $row = $this->getRowByContent($content);
        $this->checkRowCheckbox($row);
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
     * @return NodeElement
     * @throws \Exception
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

    public function assertNoRecords()
    {
        expect($this->getRows())->toHaveCount(0);
    }

    /**
     * @param string $content
     * @param string $action
     * @throws ExpectationException
     */
    public function clickActionLink($content, $action)
    {
        $row = $this->getRowByContent($content);
        $link = $this->getActionLink($action, $row);
        $link->click();
    }

    /**
     * @param $action
     * @param NodeElement $row
     * @return NodeElement
     * @throws ElementNotFoundException
     */
    public function getActionLink($action, NodeElement $row)
    {
        if ($showMoreLink = $row->find('named', ['link', '...'])) {
            $showMoreLink->mouseOver();
            $link = $this->elementFactory
                ->createElement('GridFloatingMenu')
                ->find('named', ['link', ucfirst($action)]);
        } else {
            $link = $row->find('named', ['link', $action]);
        }

        if (!$link) {
            throw new ElementNotFoundException($this->getDriver(), 'link', 'id|title|alt|text', $action);
        }

        return $link;
    }

    /**
     * @return NodeElement[]
     */
    public function getRows()
    {
        return $this->findAll('css', 'tbody tr');
    }

    /**
     * @param string $content
     * @return NodeElement
     * @throws ExpectationException
     */
    public function getRowByContent($content)
    {
        $rows = $this->getRows();

        foreach ($rows as $row) {
            if (false !== strpos($row->getText(), $content)) {
                return $row;
            }
        }

        throw new ExpectationException('Grid has no records', $this->session->getDriver());
    }

    /**
     * Try to guess type of value and return that data in that type
     * @param string $value
     * @return \DateTime|int|string
     */
    protected function normalizeValueByGuessingType($value)
    {
        $value = trim($value);

        if (preg_match('/^[0-9]+$/', $value)) {
            return (int) $value;
        } elseif (preg_match('/^\p{Sc}(?P<amount>[0-9]+)$/', $value, $matches)) {
            return (int) $matches['amount'];
        } elseif ($date = date_create($value)) {
            return $date;
        }

        return $value;
    }

    /**
     * @param NodeElement $row
     * @throws ExpectationException
     */
    protected function checkRowCheckbox(NodeElement $row)
    {
        $rowCheckbox = $row->find('css', '[type="checkbox"]');

        if (!$rowCheckbox) {
            throw new ExpectationException(
                sprintf('No mass action checkbox found for "%s"', $row->getText()),
                $this->getDriver()
            );
        }

        $rowCheckbox->click();
    }
}
