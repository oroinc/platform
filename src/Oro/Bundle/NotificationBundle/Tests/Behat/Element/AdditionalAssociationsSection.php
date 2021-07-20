<?php

namespace Oro\Bundle\NotificationBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;

class AdditionalAssociationsSection extends Form
{
    public function setCheckBoxByName(string $name, bool $check = true): void
    {
        $label = $this->find('xpath', sprintf("//label[text()='%s']", $name));
        self::assertNotNull($label, sprintf("Could not find '%s' additional association", $name));

        $checkbox = $label->getParent()->find('css', 'input');
        self::assertNotNull($checkbox, sprintf("Could not find '%s' additional association's checkbox", $name));

        if ($check) {
            $checkbox->check();
        } else {
            $checkbox->uncheck();
        }
    }
}
