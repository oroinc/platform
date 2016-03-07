<?php

namespace Oro\Bundle\ActionBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\ActionBundle\Model\ActionData;

/**
 * This listener removes attributes from context if they are not present in form in PRE_SET_DATA event
 * and returns all values including submitted values back to original context in SUBMIT event.
 *
 * This logic is used to avoid validation of attributes that are not in current form.
 */
class RequiredAttributesListener implements EventSubscriberInterface
{
    /** @var ActionData */
    protected $data;

    /** @var array */
    protected $attributeNames;

    /**
     * @param array $attributeNames
     */
    public function initialize(array $attributeNames)
    {
        $this->attributeNames = $attributeNames;
    }

    /**
     * Extract only required attributes for form and create new data based on them
     *
     * @param FormEvent $event
     */
    public function onPreSetData(FormEvent $event)
    {
        /** @var ActionData $data */
        $data = $event->getData();
        if ($data instanceof ActionData) {
            $this->data = $data;

            $event->setData(new ActionData($data->getValues($this->attributeNames)));
        }
    }

    /**
     * Copy submitted data to existing data
     *
     * @param FormEvent $event
     */
    public function onSubmit(FormEvent $event)
    {
        /** @var ActionData $data */
        $data = $event->getData();
        if ($this->data && $data instanceof ActionData) {
            foreach ($data->getValues() as $name => $value) {
                $this->data->$name = $value;
            }

            $event->setData($this->data);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'onPreSetData',
            FormEvents::SUBMIT => 'onSubmit'
        ];
    }
}
