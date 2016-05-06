<?php

namespace Oro\Bundle\CalendarBundle\Twig;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Strategy\Recurrence\DelegateStrategy;
use Oro\Component\PropertyAccess\PropertyAccessor;

class RecurrenceExtension extends \Twig_Extension
{
    /** @var DelegateStrategy */
    protected $delegateStrategy;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    // @TODO unit test

    /**
     * @param DelegateStrategy $delegateStrategy
     * @param TranslatorInterface $translator
     */
    public function __construct(DelegateStrategy $delegateStrategy, TranslatorInterface $translator)
    {
        $this->delegateStrategy = $delegateStrategy;
        $this->translator = $translator;
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
            ),
            'get_recurrence_pattern_by_attributes' => new \Twig_Function_Method(
                $this,
                'getRecurrencePatternByAttributes'
            ),
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
     * @param null|int $id
     * @param array $attributes
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\PropertyAccess\Exception\InvalidPropertyPathException
     * @throws \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     */
    public function getRecurrencePatternByAttributes($id, array $attributes)
    {
        if ($id === null) {
            return $this->translator->trans('oro.calendar.calendarevent.recurrence.na');
        }
        $propertyAccessor = $this->getPropertyAccessor();
        $recurrence = new Recurrence();
        foreach ($attributes as $attr => $value) {
            $propertyAccessor->setValue($recurrence, $attr, $value);
        }
        // @TODO use RecurrenceValidator

        return $this->getRecurrencePattern($recurrence);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_recurrence';
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if ($this->propertyAccessor === null) {
            $this->propertyAccessor = new PropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
