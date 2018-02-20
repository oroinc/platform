<?php

namespace Oro\Bundle\ApiBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * This file is inspired by Symfony ResizeFormListener
 * @see \Symfony\Component\Form\Extension\Core\EventListener\ResizeFormListener
 */
class CollectionListener implements EventSubscriberInterface
{
    /** @var CollectionEntryFactory */
    protected $entryFactory;

    /**
     * @param CollectionEntryFactory $entryFactory
     */
    public function __construct(CollectionEntryFactory $entryFactory)
    {
        $this->entryFactory = $entryFactory;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT   => 'preSubmit',
            // (MergeCollectionListener, MergeDoctrineCollectionListener)
            FormEvents::SUBMIT       => ['onSubmit', 50]
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        if (null === $data) {
            $data = [];
        }

        if (!$this->isSupportedData($data)) {
            throw new UnexpectedTypeException($data, 'array or (\Traversable and \ArrayAccess)');
        }

        // First remove all rows
        foreach ($form as $name => $child) {
            $form->remove($name);
        }

        // Then add all rows again in the correct order
        $factory = $form->getConfig()->getFormFactory();
        foreach ($data as $name => $value) {
            $form->add($this->entryFactory->createEntry($factory, $name));
        }
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        if (!$this->isSupportedData($data)) {
            $data = [];
        }

        // Remove all empty rows
        foreach ($form as $name => $child) {
            if (!isset($data[$name])) {
                $form->remove($name);
            }
        }

        // Add all additional rows
        $factory = $form->getConfig()->getFormFactory();
        foreach ($data as $name => $value) {
            if (!$form->has($name)) {
                $form->add($this->entryFactory->createEntry($factory, $name));
            }
        }
    }

    /**
     * @param FormEvent $event
     */
    public function onSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        // At this point, $data is an array or an array-like object that already contains the
        // new entries, which were added by the data mapper. The data mapper ignores existing
        // entries, so we need to manually unset removed entries in the collection.

        if (null === $data) {
            $data = [];
        }

        if (!$this->isSupportedData($data)) {
            throw new UnexpectedTypeException($data, 'array or (\Traversable and \ArrayAccess)');
        }

        // The data mapper only adds, but does not remove items, so do this here
        $toDelete = [];
        foreach ($data as $name => $child) {
            if (!$form->has($name)) {
                $toDelete[] = $name;
            }
        }
        foreach ($toDelete as $name) {
            unset($data[$name]);
        }

        $event->setData($data);
    }

    /**
     * @param mixed $data
     *
     * @return bool
     */
    protected function isSupportedData($data)
    {
        return is_array($data) || ($data instanceof \Traversable && $data instanceof \ArrayAccess);
    }
}
