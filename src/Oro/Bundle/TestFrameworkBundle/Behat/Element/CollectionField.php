<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;

class CollectionField extends Element
{
    /**
     * {@inheritdoc}
     */
    public function setValue($values)
    {
        $this->removeAllRows();
        $this->addNewRows($values);

        $inputs = $this->findAllTextFields();

        foreach ($values as $value) {
            $input = array_shift($inputs);
            $input->setValue(trim($value));
        }
    }

    /**
     * Collection field has radio button for set on field as primary.
     * See phones, emails fields in Contact (CRM) create page
     *
     * @param string $value
     */
    public function setFieldAsPrimary($value)
    {
        $inputs = $this->findAllTextFields();

        /** @var Element $input */
        foreach ($inputs as $input) {
            if ($input->getValue() == $value) {
                $input->getParent()->find('css', 'input[type=radio]')->click();

                return;
            }
        }

        self::fail(sprintf('Not found "%s" value', $value));
    }

    /**
     * @param int $number starts from 1
     * @throws \InvalidArgumentException
     */
    public function removeRow($number)
    {
        $removeRowButton = $this->find('xpath', sprintf('(//button[contains(@class, "removeRow")])[%s]', $number));

        if (!$removeRowButton) {
            throw new \InvalidArgumentException(
                sprintf('Cannot remove collection element with %s number', $number)
            );
        }

        $removeRowButton->click();
    }

    /**
     * Find any text inputs. Type can be text, email, password etc.
     *
     * @return NodeElement[]
     */
    protected function findAllTextFields()
    {
        return $this->findAll(
            'css',
            'input:not([type=button])'
            .':not([type=checkbox])'
            .':not([type=hidden])'
            .':not([type=image])'
            .':not([type=radio])'
            .':not([type=reset])'
            .':not([type=submit])'
        );
    }

    protected function removeAllRows()
    {
        /** @var Element $removeRawButton */
        while ($removeRawButton = $this->find('css', '.removeRow')) {
            $removeRawButton->click();
        }
    }

    /**
     * @param array|TableNode $values
     * @param int $withAdditionalRows
     */
    protected function addNewRows($values, $withAdditionalRows = 0)
    {
        $rows = $values;
        if (is_a($values, TableNode::class)) {
            $rows = $values->getRows();
            // Unset first row as it is used for table caption
            unset($rows[0]);
        }

        for ($i = 0; $i < (count($rows) + $withAdditionalRows); $i++) {
            $this->clickLink('Add');
        }
    }
}
