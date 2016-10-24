<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\InputMethod;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\InputValue;

class GridRow extends Element
{
    /**
     * @param int $number Row index number starting from 0
     * @return NodeElement
     */
    public function getCellByNumber($number)
    {
        $number = (int) $number;
        $columns = $this->findAll('css', 'td');
        self::assertArrayHasKey($number, $columns);

        return $columns[$number];
    }

    public function checkMassActionCheckbox()
    {
        $rowCheckbox = $this->find('css', '[type="checkbox"]');
        self::assertNotNull($rowCheckbox, sprintf('No mass action checkbox found for "%s"', $this->getText()));

        $rowCheckbox->click();
    }

    /**
     * @param string $header Column header name
     * @return \DateTime|int|string
     */
    public function getCellValue($header)
    {
        /** @var GridHeader $gridHeader */
        $gridHeader = $this->elementFactory->createElement('GridHeader');
        $columnNumber = $gridHeader->getColumnNumber($header);

        return $this->normalizeValueByGuessingType(
            $this->getCellByNumber($columnNumber)->getText()
        );
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
        /** @var GridHeader $gridHeader */
        $gridHeader = $this->elementFactory->createElement('GridHeader');
        $columnNumber = $gridHeader->getColumnNumber($header);

        /** @var NodeElement $cell */
        $cell = $this->getCellByNumber($columnNumber);
        $cell->mouseOver();

        /** @var NodeElement $pencilIcon */
        $pencilIcon = $cell->find('css', 'i[data-role="edit"]');
        self::assertTrue($pencilIcon->isValid());
        $pencilIcon->click();


        $this->elementFactory->createElement('OroForm')->fillField(
            'value',
            new InputValue(InputMethod::TYPE, $value)
        );

        $this->getDriver()->waitForAjax();
        $cell->find('css', 'button[title="Save changes"]')->click();
    }

    /**
     * Try to guess type of value and return that data in that type
     * @param string $value
     * @return \DateTime|int|string
     */
    private function normalizeValueByGuessingType($value)
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
}
