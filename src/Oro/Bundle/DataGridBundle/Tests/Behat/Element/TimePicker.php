<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class TimePicker extends Element
{
    /**
     * @param \DateTime $dateTime
     */
    public function setValue($dateTime)
    {
        $this->click();

        $timeSelect = $this->getPage()->findVisible('css', '.ui-timepicker-wrapper');
        $time = $this->formatTime($dateTime);

        /** @var NodeElement $li */
        foreach ($timeSelect->findAll('css', 'li') as $li) {
            if ($time == $this->formatTime(new \DateTime($li->getText()))) {
                $li->mouseOver();
                $li->click();
                $mask = $this->getPage()->findVisible('css', '#oro-dropdown-mask');
                if (!empty($mask)) {
                    $mask->click();
                }

                return;
            }
        }

        self::fail(sprintf('Time "%s" not found in select', $time));
    }

    /**
     * @param \DateTime $dateTime
     * @return string
     */
    protected function formatTime(\DateTime $dateTime)
    {
        $formatedTime = clone $dateTime;

        $minutes = (int) $dateTime->format('i');
        if ($minutes >= 15 && $minutes < 45) {
            $minutes = '30';
        } else {
            $minutes = '00';
        }

        $formatedTime->setTime($formatedTime->format('G'), $minutes);

        return $formatedTime->format('g:i A');
    }
}
