<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
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
     * Inline edit row cell
     *
     * @param string $header Column header name
     * @param string $value
     * @throws \Behat\Mink\Exception\ElementNotFoundException
     */
    public function setCellValue($header, $value)
    {
        /** @var TableHeader $gridHeader */
        $gridHeader = $this->elementFactory->createElement(static::HEADER_ELEMENT, $this->getParent()->getParent());
        $columnNumber = $gridHeader->getColumnNumber($header);

        /** @var NodeElement $cell */
        $cell = $this->getCellByNumber($columnNumber);
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

        $saveButton = $this->spin(function (GridRow $gridRow) {
            return $gridRow->find('css', 'button[title="Save changes"]');
        });

        self::assertNotNull($saveButton, sprintf('Save button for "%s" inline edit not found', $header));
        $saveButton->click();
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
}
