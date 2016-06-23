<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class DateTimePicker extends Element
{
    /**
     * @param \DateTime $dateTime
     * @throws ExpectationException
     */
    public function chooseDate(\DateTime $dateTime)
    {
        $this->find('css', 'input.datepicker-input')->click();
        $this->find('css', '.ui-datepicker-month')->selectOption($dateTime->format('M'));
        $this->find('css', '.ui-datepicker-year')->selectOption($dateTime->format('Y'));
        $dateValue = (string) $dateTime->format('j');

        /** @var NodeElement $date */
        foreach ($this->findAll('css', '.ui-datepicker-calendar tbody a') as $date) {
            if ($date->getText() == $dateValue) {
                $date->click();

                return;
            }
        }

        throw new ExpectationException(
            sprintf('Can\'t choose "%s" date', $dateTime->format('Y-M-j')),
            $this->getDriver()
        );
    }
}
