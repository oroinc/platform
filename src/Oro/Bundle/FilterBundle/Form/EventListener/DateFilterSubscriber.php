<?php

namespace Oro\Bundle\FilterBundle\Form\EventListener;

use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;
use Oro\Bundle\FilterBundle\Utils\DateFilterModifier;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

/**
 * Responsible for formatting the datetime according to the time zone
 * and copying submitted "start" and "end" values to model data under "start_original" and "end_original" keys.
 */
class DateFilterSubscriber implements EventSubscriberInterface
{
    /** @var DateFilterModifier */
    protected $dateFilterModifier;

    /** @var array */
    protected $processed = [];

    /** @var array */
    protected static $partChoicesMap = [
        DateModifierInterface::PART_MONTH   => [1, 12],
        DateModifierInterface::PART_DOW     => [1, 7],
        DateModifierInterface::PART_WEEK    => [1, 53],
        DateModifierInterface::PART_DAY     => [1, 31],
        DateModifierInterface::PART_QUARTER => [1, 4],
        DateModifierInterface::PART_DOY     => [1, 366],
    ];

    public function __construct(DateFilterModifier $modifier)
    {
        $this->dateFilterModifier = $modifier;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SUBMIT => 'preSubmit',
            FormEvents::SUBMIT     => 'submit'
        ];
    }

    /**
     * Parses date expressions
     * If date part given, then replace value fields by choice fields with specific to that value choices
     */
    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        $config = $form->getConfig();

        $oid = spl_object_hash($form);
        if (!empty($this->processed[$oid])) {
            // ensure that data is not processed
            // in case when DateTimeFilter already process and parent subscription is not necessary
            return;
        }

        // Remembers submitted values.
        // It is required to correct work of date interval filters, e.g. the "day without year" variable.
        $context = $this->getSubmitContext($config);
        if (isset($data['value']['start'])) {
            $context->addValue('start_original', $data['value']['start']);
        }
        if (isset($data['value']['end'])) {
            $context->addValue('end_original', $data['value']['end']);
        }

        $children = array_keys($form->get('value')->all());
        $data = $this->dateFilterModifier->modify($data, $children);
        // replace value form children to needed sub forms in case when part is selected
        if (array_key_exists($data['part'], static::$partChoicesMap)) {
            $min = static::$partChoicesMap[$data['part']][0];
            $max = static::$partChoicesMap[$data['part']][1];
            $this->replaceValueFields($form->get('value'), $this->getChoices($min, $max));
        } elseif ($data['part'] === DateModifierInterface::PART_YEAR) {
            $this->replaceValueFields($form->get('value'), array_flip(range(date('Y') - 100, date('Y') + 50)));
        }
        $event->setData($data);
        $this->processed[$oid] = true;
    }

    public function submit(FormEvent $event)
    {
        // Adds submitted values to model data.
        // It is required to correct work of date interval filters, e.g. the "day without year" variable.
        $data = $event->getData();
        if (\is_array($data)) {
            $event->setData($this->getSubmitContext($event->getForm()->getConfig())->applyValues($data));
        }
    }

    /**
     * Returns array combined by range of $min and $max for keys and for values
     *
     * @param int $min
     * @param int $max
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
     */
    private function replaceValueFields(FormInterface $form, array $choices)
    {
        $children = array_keys($form->all());
        foreach ($children as $child) {
            $form->add($child, ChoiceType::class, ['choices' => array_flip($choices)]);
        }
    }

    private function getSubmitContext(FormConfigInterface $config): DateFilterSubmitContext
    {
        return $config->getOption('submit_context');
    }
}
