<?php

namespace Oro\Bundle\ApiBundle\Form\EventListener;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * This listener is intended to
 * * convert submitted data to form acceptable by ScalarObjectType form type
 * * add an entity processed by ScalarObjectType form type to the list of additional entities
 *   of API context within this form is processed
 */
class ScalarObjectListener implements EventSubscriberInterface
{
    private bool $setDataToNull = false;

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SUBMIT  => 'preSubmit',
            FormEvents::SUBMIT      => 'onSubmit',
            FormEvents::POST_SUBMIT => ['postSubmit', -250]
        ];
    }

    public function preSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        $submittedData = $event->getData();
        if (null === $submittedData && !$form->getConfig()->getRequired()) {
            $this->setDataToNull = true;
            foreach ($form->all() as $child) {
                $form->remove($child->getName());
            }
        } else {
            $event->setData([ConfigUtil::IGNORE_PROPERTY_PATH => $event->getData()]);
        }
    }

    public function onSubmit(FormEvent $event): void
    {
        if ($this->setDataToNull) {
            $event->setData(null);
        }
    }

    public function postSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        if ($form->getConfig()->getInheritData()) {
            return;
        }

        $entity = $form->getData();
        if (null === $entity) {
            return;
        }

        FormUtil::getApiContext($form)?->addAdditionalEntity($entity);
    }
}
