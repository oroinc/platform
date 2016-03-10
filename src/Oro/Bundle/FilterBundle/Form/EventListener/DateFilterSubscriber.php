<?php

namespace Oro\Bundle\FilterBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use Carbon\Carbon;

use Oro\Bundle\FilterBundle\Expression\Date\Compiler;
use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Oro\Bundle\FilterBundle\Expression\Date\ExpressionResult;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class DateFilterSubscriber implements EventSubscriberInterface
{
    /** @var Compiler */
    protected $expressionCompiler;

    /** @var array */
    protected $processed = [];

    /**
     * @param Compiler $compiler
     */
    public function __construct(Compiler $compiler)
    {
        $this->expressionCompiler = $compiler;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SUBMIT => 'preSubmit'
        ];
    }

    /**
     * Parses date expressions
     * If date part given, then replace value fields by choice fields with specific to that value choices
     *
     * @param FormEvent $event
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) many cases in switch - have no sense to refactor this.
     */
    public function preSubmit(FormEvent $event)
    {
        $compiler = $this->expressionCompiler;

        $data = $event->getData();
        $form = $event->getForm();

        $oid = spl_object_hash($form);
        if (!empty($this->processed[$oid])) {
            // ensure that data is not processed
            // in case when DateTimeFilter already process and parent subscription is not necessary
            return;
        }

        $children = array_keys($form->get('value')->all());
        $this->modifyDateForEqualType($data);
        $this->modifyPartByVariable($data);
        // compile expressions
        $this->mapValues(
            $children,
            $data,
            function ($data) use ($compiler) {
                return $compiler->compile($data);
            }
        );
        $data['part'] = isset($data['part']) ? $data['part'] : null;

        // change value type depending on date part
        // replace value form children to needed sub forms in case when part is selected
        switch ($data['part']) {
            case DateModifierInterface::PART_MONTH:
                $this->mapValues($children, $data, $this->getDatePartAccessorClosure('m'));
                $this->replaceValueFields($form->get('value'), $this->getChoices(1, 12));
                break;
            case DateModifierInterface::PART_DOW:
                $this->mapValues($children, $data, $this->getDatePartAccessorClosure('N'));
                $this->replaceValueFields($form->get('value'), $this->getChoices(1, 7));
                break;
            case DateModifierInterface::PART_WEEK:
                $this->mapValues($children, $data, $this->getDatePartAccessorClosure('W'));
                $this->replaceValueFields($form->get('value'), $this->getChoices(1, 53));
                break;
            case DateModifierInterface::PART_DAY:
                $this->mapValues($children, $data, $this->getDatePartAccessorClosure('d'));
                $this->replaceValueFields($form->get('value'), $this->getChoices(1, 31));
                break;
            case DateModifierInterface::PART_QUARTER:
                $this->mapValues(
                    $children,
                    $data,
                    function ($data) {
                        $quarter = null;
                        switch (true) {
                            case is_numeric($data):
                                $quarter = (int)$data;
                                break;
                            case ($data instanceof \DateTime):
                                $month   = (int)$data->format('m');
                                $quarter = ceil($month / 3);
                                break;
                            default:
                                throw new UnexpectedTypeException($data, 'integer or \DateTime');
                        }

                        return $quarter;
                    }
                );
                $this->replaceValueFields($form->get('value'), $this->getChoices(1, 4));
                break;
            case DateModifierInterface::PART_DOY:
                $this->mapValues($children, $data, $this->getDatePartAccessorClosure('z'));
                $this->replaceValueFields($form->get('value'), $this->getChoices(1, 366));
                break;
            case DateModifierInterface::PART_YEAR:
                $this->mapValues($children, $data, $this->getDatePartAccessorClosure('Y'));
                $this->replaceValueFields($form->get('value'), array_flip(range(date('Y') - 100, date('Y') + 50)));
                break;
            case DateModifierInterface::PART_VALUE:
            default:
                $this->mapValues(
                    $children,
                    $data,
                    function ($data) use ($compiler) {
                        // html5 format for intl
                        return $data instanceof \DateTime ? $data->format('Y-m-d H:i') : $data;
                    }
                );
                break;
        }

        $event->setData($data);
        $this->processed[$oid] = true;
    }

    /**
     * Returns array combined by range of $min and $max for keys and for values
     *
     * @param integer $min
     * @param integer $max
     *
     * @return array
     */
    protected function getChoices($min, $max)
    {
        $range = range((int)$min, (int)$max);

        return array_combine($range, $range);
    }

    /**
     * Replace values form children to "choice" type with predefined choice list
     *
     * @param FormInterface $form
     * @param array         $choices
     */
    private function replaceValueFields(FormInterface $form, array $choices)
    {
        $children = array_keys($form->all());

        foreach ($children as $child) {
            $form->add($child, 'choice', ['choices' => $choices]);
        }
    }

    /**
     * Modify filter when selected (source or value) and (equals or not equals) and today, start_of_* modifiers
     * For example: equals today convert to between from 2015-11-25 00:00:00 to 2015-11-25 23:59:59
     * It's normal user's expectations
     *
     * @param array $data
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function modifyDateForEqualType(&$data)
    {
        if (!isset($data['part'], $data['type'])) {
            return;
        }

        $validType =
            $data['type'] == AbstractDateFilterType::TYPE_EQUAL ||
            $data['type'] == AbstractDateFilterType::TYPE_NOT_EQUAL;
        $validPart =
            $data['part'] === DateModifierInterface::PART_SOURCE ||
            $data['part'] === DateModifierInterface::PART_VALUE;

        if (isset($data['value']) && $validType && $validPart) {
            if ($data['type'] == AbstractDateFilterType::TYPE_EQUAL) {
                $date = $data['value']['start'];
            } else {
                $date = $data['value']['end'];
            }
            $result = $this->expressionCompiler->compile($date, true);

            if ($result instanceof ExpressionResult) {
                switch ($result->getVariableType()) {
                    case DateModifierInterface::VAR_TODAY:
                    case DateModifierInterface::VAR_SOW:
                    case DateModifierInterface::VAR_SOM:
                    case DateModifierInterface::VAR_SOQ:
                    case DateModifierInterface::VAR_SOY:
                        /** @var Carbon $date */
                        $date = $this->expressionCompiler->compile($date);
                        $clonedDate = clone $date;
                        if ($data['type'] == AbstractDateFilterType::TYPE_EQUAL) {
                            $data['value']['end'] = $clonedDate->endOfDay()->format('Y-m-d H:i');
                            $data['type'] = AbstractDateFilterType::TYPE_BETWEEN;
                        } else {
                            $data['type'] = AbstractDateFilterType::TYPE_NOT_BETWEEN;
                            $data['value']['start'] = $data['value']['end'];
                            $data['value']['end'] = $clonedDate->endOfDay()->format('Y-m-d H:i');
                        }
                        break;
                }
            }
        }
    }

    /**
     * Doesn't matter which part user was selected. This variables should contain own certain part.
     * To support this approach see that now grid doesn't contain 'part' select box and backend must
     * change 'part' dynamically
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @param array $data
     */
    protected function modifyPartByVariable(&$data)
    {
        if (!isset($data['part'], $data['type'])) {
            return;
        }

        foreach ($data['value'] as $field) {
            if ($field) {
                $result = $this->expressionCompiler->compile($field, true);
                switch ($result->getVariableType()) {
                    case DateModifierInterface::VAR_THIS_DAY_W_Y:
                        $data['part']=DateModifierInterface::PART_VALUE;
                        break;
                    case DateModifierInterface::VAR_THIS_MONTH:
                    case DateModifierInterface::VAR_JANUARY:
                    case DateModifierInterface::VAR_FEBRUARY:
                    case DateModifierInterface::VAR_MARCH:
                    case DateModifierInterface::VAR_APRIL:
                    case DateModifierInterface::VAR_MAY:
                    case DateModifierInterface::VAR_JUNE:
                    case DateModifierInterface::VAR_JULY:
                    case DateModifierInterface::VAR_AUGUST:
                    case DateModifierInterface::VAR_SEPTEMBER:
                    case DateModifierInterface::VAR_OCTOBER:
                    case DateModifierInterface::VAR_NOVEMBER:
                    case DateModifierInterface::VAR_DECEMBER:
                        $data['part']=DateModifierInterface::PART_MONTH;
                        break;
                }

                switch ($result->getSourceType()) {
                    case ExpressionResult::TYPE_DAYMONTH:
                        $data['part']=DateModifierInterface::PART_VALUE;
                        break;
                }
            }
        }
    }

    /**
     * Call callback for each of given value, used instead of array_map to walk safely through array
     *
     * @param array    $keys
     * @param array    $data
     * @param callable $callback
     */
    private function mapValues(array $keys, array &$data, \Closure $callback)
    {
        foreach ($keys as $key) {
            if (isset($data['value'], $data['value'][$key])) {
                $data['value'][$key] = $callback($data['value'][$key]);
            }
        }
    }

    /**
     * Returns callable that able to retrieve needed datePart from compiler result
     *
     * @param string $part
     *
     * @return callable
     */
    private function getDatePartAccessorClosure($part)
    {
        return function ($value) use ($part) {
            switch (true) {
                case is_numeric($value):
                    return (int)$value;
                    break;
                case ($value instanceof \DateTime):
                    return (int)$value->format($part);
                    break;
                default:
                    throw new UnexpectedTypeException($value, 'integer or \DateTime');
            }
        };
    }
}
