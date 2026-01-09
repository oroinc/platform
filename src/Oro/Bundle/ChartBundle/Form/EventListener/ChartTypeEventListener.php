<?php

namespace Oro\Bundle\ChartBundle\Form\EventListener;

use Oro\Bundle\ChartBundle\Exception\InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Handles form events for chart type forms to manage chart-specific data structure.
 *
 * This event listener subscribes to form events (`PRE_SET_DATA` and `SUBMIT`) to transform
 * chart form data between two formats: a flat structure used by the form and a nested
 * structure organized by chart name and options groups (settings, data_schema). It ensures
 * that chart configuration data is properly structured when loading and submitting forms.
 */
class ChartTypeEventListener implements EventSubscriberInterface
{
    /**
     * @var array
     */
    protected $optionsGroups = ['settings', 'data_schema'];

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::SUBMIT       => 'onSubmit'
        ];
    }

    /**
     * @throws InvalidArgumentException
     */
    public function onSubmit(FormEvent $event)
    {
        $formData = $event->getData();

        if (!$formData || !isset($formData['name'])) {
            $event->setData([]);

            return;
        }

        $name = $formData['name'];

        foreach ($this->optionsGroups as $optionsGroup) {
            if (isset($formData[$optionsGroup][$name])) {
                $formData[$optionsGroup] = $formData[$optionsGroup][$name];
            }
        }

        $event->setData($formData);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function preSetData(FormEvent $event)
    {
        $formData = $event->getData();

        if (!$formData || !isset($formData['name'])) {
            $event->setData([]);

            return;
        }

        $name = $formData['name'];

        foreach ($this->optionsGroups as $optionsGroup) {
            if (isset($formData[$optionsGroup])) {
                $data = $formData[$optionsGroup];

                foreach (array_keys($data) as $key) {
                    unset($formData[$optionsGroup][$key]);
                }

                $formData[$optionsGroup][$name] = $data;
            }
        }

        $event->setData($formData);
    }
}
