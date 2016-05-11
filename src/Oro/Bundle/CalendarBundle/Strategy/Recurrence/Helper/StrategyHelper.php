<?php

namespace Oro\Bundle\CalendarBundle\Strategy\Recurrence\Helper;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StrategyHelper
{
    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * StrategyHelper constructor.
     *
     * @param ValidatorInterface $validator
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /** @var array */
    protected static $instanceRelativeValues = [
        Recurrence::INSTANCE_FIRST => 'first',
        Recurrence::INSTANCE_SECOND => 'second',
        Recurrence::INSTANCE_THIRD => 'third',
        Recurrence::INSTANCE_FOURTH => 'fourth',
        Recurrence::INSTANCE_LAST => 'last',
    ];

    /** @var array */
    protected static $weekdays = [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
    ];

    /** @var array */
    protected static $weekends = [
        'saturday',
        'sunday',
    ];

    /**
     * Returns recurrence instance relative value by its key.
     *
     * @param $key
     *
     * @return null|string
     */
    public function getInstanceRelativeValue($key)
    {
        return empty(self::$instanceRelativeValues[$key]) ? null : self::$instanceRelativeValues[$key];
    }

    /**
     * Validates recurrence entity according to its validation rules.
     *
     * @param Recurrence $recurrence
     *
     * @return self
     *
     * @throws \RuntimeException
     */
    public function validateRecurrence(Recurrence $recurrence)
    {
        $errors = $this->validator->validate($recurrence);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }

            throw new \RuntimeException('Recurrence is invalid: ' . json_encode($errorMessages));
        }

        return $this;
    }

    /**
     * Returns relative value for dayOfWeek of recurrence entity.
     *
     * @param array $dayOfWeek
     *
     * @return string
     */
    public function getDayOfWeekRelativeValue($dayOfWeek)
    {
        sort($dayOfWeek);
        sort(self::$weekends);
        if (self::$weekends == $dayOfWeek) {
            return 'weekend';
        }

        sort(self::$weekdays);
        if (self::$weekdays == $dayOfWeek) {
            return 'weekday';
        }

        if (count($dayOfWeek) == 7) {
            return 'day';
        }

        //returns first element
        return reset($dayOfWeek);
    }
}
