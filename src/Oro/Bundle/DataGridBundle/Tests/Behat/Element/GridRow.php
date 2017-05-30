<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\InputMethod;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\InputValue;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\TableHeader;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\TableRow;

class GridRow extends TableRow
{
    const HEADER_ELEMENT = 'GridHeader';

    /**
     * @param int $cellNumber
     */
    public function checkMassActionCheckbox($cellNumber = 0)
    {
        $rowCheckbox = $this->getCellByNumber($cellNumber)->find('css', '[type="checkbox"]');
        self::assertNotNull($rowCheckbox, sprintf('No mass action checkbox found for "%s"', $this->getText()));

        if ($rowCheckbox->isChecked()) {
            return;
        }

        $rowCheckbox->click();
    }

    /**
     * @param int $cellNumber
     */
    public function uncheckMassActionCheckbox($cellNumber = 0)
    {
        $rowCheckbox = $this->getCellByNumber($cellNumber)->find('css', '[type="checkbox"]');
        self::assertNotNull($rowCheckbox, sprintf('No mass action checkbox found for "%s"', $this->getText()));

        if (!$rowCheckbox->isChecked()) {
            return;
        }

        $rowCheckbox->click();
    }

    /**
     * @param string $header
     * @return NodeElement
     */
    public function getCell($header)
    {
        /** @var TableHeader $gridHeader */
        $gridHeader = $this->elementFactory->createElement(static::HEADER_ELEMENT, $this->getParent()->getParent());
        $columnNumber = $gridHeader->getColumnNumber($header);

        /** @var NodeElement $cell */
        $cell = $this->getCellByNumber($columnNumber);

        return $cell;
    }

    /**
     * Inline edit row cell
     *
     * @param string $header Column header name
     * @param string $value
     * @return NodeElement
     */
    public function setCellValue($header, $value)
    {
        $cell = $this->getCell($header);
        $cell->mouseOver();

        /** @var NodeElement $pencilIcon */
        $pencilIcon = $cell->find('css', 'i[data-role="edit"]');
        self::assertNotNull($pencilIcon, "Cell with '$header' is not inline editable");
        self::assertTrue($pencilIcon->isValid(), "Cell with '$header' is not inline editable");
        self::assertTrue($pencilIcon->isVisible(), "Cell with '$header' is not inline editable");
        $pencilIcon->click();

        $this->getElement('OroForm')->fillField(
            'value',
            new InputValue(InputMethod::TYPE, $value)
        );

        $this->getDriver()->waitForAjax();

        return $cell;
    }

    /**
     * Inline edit row cell and save
     *
     * @param string $header Column header name
     * @param string $value
     */
    public function setCellValueAndSave($header, $value)
    {
        $cell = $this->setCellValue($header, $value);

        $saveButton = $this->spin(function (GridRow $gridRow) {
            return $gridRow->find('css', 'button[title="Save changes"]');
        });

        self::assertNotNull($saveButton, sprintf('Save button for "%s" inline edit not found', $header));
        $saveButton->click();
    }

    /**
     * Inline edit row cell and cancel
     *
     * @param string $header Column header name
     * @param string $value
     */
    public function setCellValueAndCancel($header, $value)
    {
        $cell = $this->setCellValue($header, $value);
        $cell->find('css', 'button[title="Cancel"]')->click();
    }

    /**
     * Inline edit row cell by double click and save
     *
     * @param string $header Column header name
     * @param string $value
     */
    public function setCellValueByDoubleClick($header, $value)
    {
        $cell = $this->getCell($header);

        $cell->mouseOver();

        // This doesn't work properly with chromedriver as it doesn't generate a pair of mouseDown/mouseUp events
        // for the double click and only generates one. So our logic for mouseUp event doesn't work.
        // This works with PhantomJs fine.
        $cell->doubleClick();

        $this->getElement('OroForm')->fillField(
            'value',
            new InputValue(InputMethod::TYPE, $value)
        );

        $this->getDriver()->waitForAjax();
    }

    /**
     * @param string $action anchor of link - Create, Edit, Delete etc.
     * @return NodeElement|null
     */
    public function findActionLink($action)
    {
        if ($showMoreLink = $this->find('named', ['link', '...'])) {
            $showMoreLink->mouseOver();
            $link = $this->elementFactory
                ->createElement('GridFloatingMenu')
                ->find('named', ['link', ucfirst($action)]);
        } else {
            $link = $this->find('named', ['link', ucfirst($action)]);
        }

        return $link;
    }

    /**
     * @param $action
     * @return NodeElement
     */
    public function getActionLink($action)
    {
        $link = $this->findActionLink($action);
        self::assertNotNull($link, sprintf('Row "%s" has no "%s" action', $this->getText(), $action));

        return $link;
    }

    /**
     * @return Element[]
     */
    public function getActionLinks()
    {
        if (null !== ($showMoreLink = $this->find('named', ['link', '...']))) {
            $showMoreLink->mouseOver();
            $links = $this->elementFactory->createElement('GridFloatingMenu')->getElements('GridRowAction');
        } else {
            $links = $this->getElements('GridRowAction');
        }

        return $links;
    }
}
