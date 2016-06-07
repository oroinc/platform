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

    public function getRecordsNumber()
    {
        /** @var GridPaginator $paginator */
        $paginator = $this->elementFactory->createElement('GridPaginator');

        return $paginator->getTotalRecordsCount();
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
            $massActionCell = $row->find('css', '.grid-body-cell-massAction');
            $massActionCell->click();
        }
    }

    /**
     * @return NodeElement
     * @throws \Exception
     */
    private function getMassActionButton()
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
    private function getMassActionLink($title)
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

        if ($showMoreLink = $row->find('named', ['link', '...'])) {
            $showMoreLink->mouseOver();
            $this->elementFactory
                ->createElement('GridFloatingMenu')
                ->find('named', ['link', ucfirst($action)])
                ->click();
        } else {
            $row->find('named', ['link', $action])->click();
        }

    }

    /**
     * @return NodeElement[]
     */
    private function getRows()
    {
        return $this->findAll('css', 'tbody tr');
    }

    /**
     * @param string $content
     * @return NodeElement
     * @throws ExpectationException
     */
    protected function getRowByContent($content)
    {
        $rows = $this->getRows();

        foreach ($rows as $row) {
            if (false !== strpos($row->getText(), $content)) {
                return $row;
            }
        }

        throw new ExpectationException('Grid has no records', $this->session->getDriver());
    }
}
