<?php

namespace Oro\Bundle\ChartBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\ChartBundle\Exception\InvalidArgumentException;

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

        if (!$formData || !isset($formData['type'])) {
            return;
        }

        $type = $formData['type'];

        foreach ($this->optionsGroups as $optionsGroup) {
            if (isset($formData[$optionsGroup][$type])) {
                $formData[$optionsGroup] = $formData[$optionsGroup][$type];
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

        if (!$formData) {
            return;
        }

        if (!isset($formData['type'])) {
            throw new InvalidArgumentException('Type is missing');
        }

        $type = $formData['type'];

        foreach ($this->optionsGroups as $optionsGroup) {
            if (isset($formData[$optionsGroup])) {
                $data = $formData[$optionsGroup];

                foreach (array_keys($data) as $key) {
                    unset($formData[$optionsGroup][$key]);
                }

                $formData[$optionsGroup][$type] = $data;
            }
        }

        $event->setData($formData);
    }
}
