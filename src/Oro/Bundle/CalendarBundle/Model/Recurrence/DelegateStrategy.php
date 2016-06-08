<?php

namespace Oro\Bundle\CalendarBundle\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;

class DelegateStrategy implements StrategyInterface
{
    /** @var StrategyInterface[] */
    protected $elements = [];

    /**
     * Adds recurrence strategy.
     *
     * @param StrategyInterface $strategy
     *
     * @return DelegateStrategy
     */
    public function add(StrategyInterface $strategy)
    {
        $this->elements[$strategy->getName()] = $strategy;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getOccurrences(Recurrence $recurrence, \DateTime $start, \DateTime $end)
    {
        $delegate = $this->match($recurrence);

        if (!$delegate) {
            throw new \InvalidArgumentException(
                sprintf('Cannot find recurrence strategy for "%s" field.', $recurrence->getRecurrenceType())
            );
        }

        return $delegate->getOccurrences($recurrence, $start, $end);
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Recurrence $recurrence)
    {
        return $this->match($recurrence) !== null;
    }

    /**
     * Checks if strategy can be used and returns its instance.
     *
     * @param Recurrence $recurrence
     *
     * @return null|StrategyInterface
     */
    protected function match(Recurrence $recurrence)
    {
        foreach ($this->elements as $strategy) {
            /** @var StrategyInterface $strategy */
            if ($strategy->supports($recurrence)) {
                return $strategy;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getTextValue(Recurrence $recurrence)
    {
        $delegate = $this->match($recurrence);

        if (!$delegate) {
            throw new \InvalidArgumentException(
                sprintf('Cannot find recurrence strategy for "%s" field.', $recurrence->getRecurrenceType())
            );
        }

        return $delegate->getTextValue($recurrence);
    }

    /**
     * {@inheritdoc}
     */
    public function getCalculatedEndTime(Recurrence $recurrence)
    {
        $delegate = $this->match($recurrence);

        if (!$delegate) {
            throw new \InvalidArgumentException(
                sprintf('Cannot find recurrence strategy for "%s" field.', $recurrence->getRecurrenceType())
            );
        }

        return $delegate->getCalculatedEndTime($recurrence);
    }

    /**
     * {@inheritdoc}
     */
    public function getValidationErrorMessage(Recurrence $recurrence)
    {
        $delegate = $this->match($recurrence);

        if (!$delegate) {
            throw new \InvalidArgumentException(
                sprintf('Cannot find recurrence strategy for "%s" field.', $recurrence->getRecurrenceType())
            );
        }

        return $delegate->getValidationErrorMessage($recurrence);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'recurrence_delegate';
    }
}
