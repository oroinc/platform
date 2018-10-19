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
        $rowCheckbox = $this->getMassActionCheckbox($cellNumber);
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
        $rowCheckbox = $this->getMassActionCheckbox($cellNumber);
        self::assertNotNull($rowCheckbox, sprintf('No mass action checkbox found for "%s"', $this->getText()));

        if (!$rowCheckbox->isChecked()) {
            return;
        }

        $rowCheckbox->click();
    }

    /**
     * @param int $cellNumber
     * @return bool
     */
    public function hasMassActionCheckbox($cellNumber = 0): bool
    {
        return $this->getMassActionCheckbox($cellNumber) !== null;
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
        $cell = $this->startInlineEditing($header);

        $this->getElement('OroForm')->fillField(
            'value',
            new InputValue(InputMethod::TYPE, $value)
        );

        $this->getDriver()->waitForAjax();

        return $cell;
    }

    /**
     * Start inline editing on the cell without changing the value and without saving
     *
     * @param string $header Column header name
     * @return NodeElement
     */
    public function startInlineEditing($header)
    {
        $cell = $this->getCell($header);
        $cell->mouseOver();

        /** @var NodeElement $pencilIcon */
        $pencilIcon = $cell->find('css', '[data-role="edit"]');
        self::assertNotNull($pencilIcon, "Cell with '$header' is not inline editable");
        self::assertTrue(
            $pencilIcon->isValid() && $pencilIcon->isVisible(),
            "Cell with '$header' is not inline editable"
        );

        $pencilIcon->click();

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
        $this->setCellValue($header, $value);

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
        if ($showMoreLink = $this->find('css', '.more-bar-holder .dropdown-toggle')) {
            $showMoreLink->mouseOver();
            $link = $this->waitFor(5, function () use ($action) {
                return $this->elementFactory
                    ->createElement('GridRowActionMenu')
                    ->find('named', ['link', ucfirst($action)]);
            });
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
        if (null !== ($showMoreLink = $this->find('css', '.more-bar-holder .dropdown-toggle'))) {
            $showMoreLink->mouseOver();
            $links = $this->elementFactory->createElement('GridRowActionMenu')->getElements('GridRowAction');
        } else {
            $links = $this->getElements('GridRowAction');
        }

        return $links;
    }

    /**
     * @param int $cellNumber
     * @return bool
     */
    public function isMassActionChecked($cellNumber = 0): bool
    {
        return $this->getCellByNumber($cellNumber)->isChecked();
    }

    /**
     * @param int $cellNumber
     * @return NodeElement|null
     */
    private function getMassActionCheckbox($cellNumber)
    {
        return $this->getCellByNumber($cellNumber)->find('css', '[type="checkbox"]');
    }
}
