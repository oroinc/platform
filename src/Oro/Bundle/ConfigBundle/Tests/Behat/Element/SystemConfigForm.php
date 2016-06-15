<?php

namespace Oro\Bundle\ConfigBundle\Tests\Behat\Element;

use Behat\Mink\Exception\ElementNotFoundException;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;

class SystemConfigForm extends Form
{
    /**
     * @param string $label
     * @throws ElementNotFoundException
     */
    public function uncheckUseDefaultCheckbox($label)
    {
        $selector = sprintf("label:contains('%s')", $label);
        $label = $this->find('css', $selector);

        if (null === $label) {
            throw new ElementNotFoundException($this->getDriver(), 'label', 'id|name|title|alt|value', $label);
        }

        $useDefaultLabel = $label->getParent()->getParent()->find('css', "label:contains('Use default')");
        $checkbox = $useDefaultLabel->getParent()->find('css', 'input');
        $checkbox->uncheck();
    }
}
