<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

class CollectionField extends Element
{
    /**
     * {@inheritdoc}
     */
    public function setValue(array $values)
    {
        $removeRawButtons = $this->findAll('css', '.removeRow');

        /** @var Element $removeRawButton */
        foreach ($removeRawButtons as $removeRawButton) {
            $removeRawButton->click();
        }

        array_walk($values, function () {
            $this->clickLink('Add');
        });

        $inputs = $this->findAll(
            'css',
            'input:not([type=button])'
            .':not([type=checkbox])'
            .':not([type=hidden])'
            .':not([type=image])'
            .':not([type=radio])'
            .':not([type=reset])'
            .':not([type=submit])'
        );

        foreach ($values as $value) {
            $input = array_shift($inputs);
            $input->setValue(trim($value));
        }
    }
}
