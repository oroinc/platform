<?php

namespace Oro\Bundle\CalendarBundle\Twig;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Model\Recurrence\DelegateStrategy;
use Oro\Component\PropertyAccess\PropertyAccessor;

class RecurrenceExtension extends \Twig_Extension
{
    /** @var DelegateStrategy */
    protected $delegateStrategy;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /** @var Recurrence */
    protected $model;

    /**
     * RecurrenceExtension constructor.
     *
     * @param DelegateStrategy $delegateStrategy
     * @param TranslatorInterface $translator
     * @param Recurrence $model
     */
    public function __construct(
        DelegateStrategy $delegateStrategy,
        TranslatorInterface $translator,
        Recurrence $model
    ) {
        $this->delegateStrategy = $delegateStrategy;
        $this->translator = $translator;
        $this->model = $model;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            'get_recurrence_text_value' => new \Twig_Function_Method(
                $this,
                'getRecurrenceTextValue'
            ),
            'get_recurrence_attributes_text_value' => new \Twig_Function_Method(
                $this,
                'getRecurrenceAttributesTextValue'
            ),
        ];
    }

    /**
     * @param Entity\Recurrence $recurrence
     *
     * @return string
     */
    public function getRecurrenceTextValue(Entity\Recurrence $recurrence)
    {
        return $this->delegateStrategy->getTextValue($recurrence);
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
    public function getRecurrenceAttributesTextValue($id, array $attributes)
    {
        if ($id === null) {
            return $this->translator->trans('oro.calendar.calendarevent.recurrence.na');
        }
        $propertyAccessor = $this->getPropertyAccessor();
        $recurrence = new Entity\Recurrence();
        foreach ($attributes as $attr => $value) {
            $propertyAccessor->setValue($recurrence, $attr, $value);
        }

        $this->model->validateRecurrence($recurrence);

        return $this->getRecurrenceTextValue($recurrence);
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
