<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ExpectationException;
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
     * @throws ExpectationException
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

        throw new ExpectationException(
            sprintf('Not found "%s" value', $value),
            $this->getDriver()
        );
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
     */
    protected function addNewRows($values)
    {
        array_walk($values, function () {
            $this->clickLink('Add');
        });
    }
}
