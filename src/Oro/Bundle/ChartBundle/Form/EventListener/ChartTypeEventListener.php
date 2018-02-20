<?php

namespace Oro\Bundle\ChartBundle\Form\EventListener;

use Oro\Bundle\ChartBundle\Exception\InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ChartTypeEventListener implements EventSubscriberInterface
{
    /**
     * @var array
     */
    protected $optionsGroups = ['settings', 'data_schema'];

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::SUBMIT       => 'onSubmit'
        ];
    }

    /**
     * @param FormEvent $event
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
     * @param FormEvent $event
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
