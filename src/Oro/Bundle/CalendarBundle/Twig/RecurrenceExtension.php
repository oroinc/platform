<?php

namespace Oro\Bundle\CalendarBundle\Twig;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Strategy\Recurrence\DelegateStrategy;

class RecurrenceExtension extends \Twig_Extension
{
    /** @var DelegateStrategy */
    protected $delegateStrategy;

    /**
     * @param DelegateStrategy $delegateStrategy
     */
    public function __construct(DelegateStrategy $delegateStrategy)
    {
        $this->delegateStrategy = $delegateStrategy;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            'get_recurrence_pattern' => new \Twig_Function_Method(
                $this,
                'getRecurrencePattern'
            )
        ];
    }

    /**
     * @param Recurrence $recurrence
     *
     * @return string
     */
    public function getRecurrencePattern(Recurrence $recurrence)
    {
        return $this->delegateStrategy->getRecurrencePattern($recurrence);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_recurrence';
    }
}
