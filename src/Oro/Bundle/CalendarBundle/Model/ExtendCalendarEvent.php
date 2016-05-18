<?php

namespace Oro\Bundle\CalendarBundle\Model;

use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\ActivityBundle\Model\ExtendActivity;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;

/**
 * @method AbstractEnumValue getOrigin
 * @method CalendarEvent setOrigin(AbstractEnumValue $value)
 */
class ExtendCalendarEvent implements ActivityInterface
{
    use ExtendActivity;

    /**
     * Constructor
     *
     * The real implementation of this method is auto generated.
     *
     * IMPORTANT: If the derived class has own constructor it must call parent constructor.
     */
    public function __construct()
    {
    }
}
