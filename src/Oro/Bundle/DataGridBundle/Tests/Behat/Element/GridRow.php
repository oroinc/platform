<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\InputMethod;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\InputValue;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\TableRow;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\VariableStorage;

class GridRow extends TableRow
{
    public const HEADER_ELEMENT = 'GridHeader';

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
     * Inline edit row cell
     *
     * @param string $header Column header name
     * @param string $value
     * @return NodeElement
     */
    public function setCellValue($header, $value)
    {
        $value = VariableStorage::normalizeValue($value);
        $cell = $this->startInlineEditing($header);

        //Tries to locate element several times to prevent premature ElementNotFoundException
        $formElement = $this->getElement('OroForm');
        $isElementFilled = $this->spin(function () use ($value, $formElement) {
            try {
                $formElement->fillField(
                    'value',
                    new InputValue(InputMethod::TYPE, $value)
                );
            } catch (\Throwable $exception) {
                // to prevent form element lost focus in case when driver can't find field value
                $formElement->click();

                throw $exception;
            }

            return true;
        });

        $this->assertTrue($isElementFilled, "Could not fill field in '$header' column with value '$value'");

        $this->getDriver()->waitForAjax();

        return $cell;
    }

    /**
     * Start inline editing on the cell without changing the value and without saving
     *
     * Double click is used instead of the edit icon: the icon is appended to a cell lazily, on
     * `mouseenter` only (see inline-editing-plugin.js), so a pointer that already rests on the cell
     * renders no icon at all, and re-hovering a cell that sits behind a horizontal grid scroll is
     * unreliable. Double click is bound to the same `isEditable` gate and lets the plugin scroll the
     * cell into view on its own, which makes this step independent of the pointer position.
     *
     * @param string $header Column header name
     * @return NodeElement
     */
    public function startInlineEditing($header)
    {
        $cell = $this->getCellByHeader($header);

        $isEditingStarted = $this->spin(function () use ($cell) {
            if ($cell->hasClass('edit-mode')) {
                return true;
            }

            if (!$cell->hasClass('editable')) {
                // Double click on a non-editable cell is handled as a row click and opens the record,
                // so leave such a cell alone and let the assertion below report it.
                return null;
            }

            $cell->doubleClick();

            return $cell->hasClass('edit-mode') ? true : null;
        }, 5);

        if (null === $isEditingStarted) {
            // Fallback for editors that do not switch the cell into the `edit-mode` state.
            $isEditingStarted = $this->startInlineEditingByIcon($cell);
        }

        self::assertNotNull($isEditingStarted, "Cell with '$header' is not inline editable");

        return $cell;
    }

    /**
     * @param NodeElement $cell
     * @return bool|null
     */
    private function startInlineEditingByIcon(NodeElement $cell)
    {
        return $this->spin(function () use ($cell) {
            foreach ($this->findAll('xpath', 'child::td|child::th') as $awayCell) {
                if ($awayCell->getXpath() !== $cell->getXpath()) {
                    $awayCell->mouseOver();
                    break;
                }
            }

            $cell->focus();
            $cell->mouseOver();

            /** @var NodeElement $pencilIcon */
            $pencilIcon = $cell->find('css', '[data-role="edit"]');
            if (null === $pencilIcon || !$pencilIcon->isValid() || !$pencilIcon->isVisible()) {
                return null;
            }

            $pencilIcon->click();

            return true;
        }, 5);
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

        $element = $this->elementFactory->createElement('Grid Row Save Changes');

        $saveButton = $this->spin(function (GridRow $gridRow) use ($element) {
            return $gridRow->find('xpath', $element->getXpath());
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
        $cell = $this->getCellByHeader($header);

        $cell->mouseOver();
        $cell->doubleClick();

        $this->getElement('OroForm')->fillField(
            'value',
            new InputValue(InputMethod::TYPE, $value)
        );

        $this->getDriver()->waitForAjax();
    }

    public function find(string $selector, $locator)
    {
        $text = $this->getOption('text');
        if (!$text) {
            return parent::find($selector, $locator);
        }

        $items = $this->findAll($selector, $locator);
        $itemCount = count($items);
        if (0 === $itemCount) {
            return null;
        }
        if (1 === $itemCount) {
            return current($items);
        }

        $text = trim(str_replace('"', ' ', $text));
        foreach ($items as $item) {
            $row = $item->find('xpath', '/ancestor::tr');
            if (null !== $row && null !== $row->find('xpath', sprintf('//td[normalize-space()="%s"]', $text))) {
                return $item;
            }
        }

        return reset($items);
    }

    /**
     * @param string $action anchor of link - Create, Edit, Delete etc.
     * @return NodeElement|null
     */
    public function findActionLink($action)
    {
        if ($showMoreLink = $this->find('css', '.more-bar-holder .dropdown-toggle')) {
            $showMoreLink->focus();
            $showMoreLink->mouseOver();
            $link = $this->spin(function () use ($action) {
                $link = $this->elementFactory
                    ->createElement('GridRowActionMenu')
                    ->find('named', ['link', ucfirst($action)]);
                return $link->isVisible() ? $link : null;
            }, 5);
        } else {
            $link = $this->find('named', ['link', ucfirst($action)]);
        }

        return $link;
    }

    /**
     * @param string $action
     * @param bool $failIfNotFound
     * @return NodeElement|null
     */
    public function getActionLink($action, $failIfNotFound = true)
    {
        $link = $this->findActionLink($action);
        if ($failIfNotFound) {
            self::assertNotNull($link, sprintf('Row "%s" has no "%s" action', $this->getText(), $action));
        }

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
