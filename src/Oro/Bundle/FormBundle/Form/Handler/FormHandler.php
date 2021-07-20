<?php

namespace Oro\Bundle\FormBundle\Form\Handler;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\FormBundle\Event\FormHandler\Events;
use Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class FormHandler implements FormHandlerInterface
{
    use RequestHandlerTrait;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    public function __construct(EventDispatcherInterface $eventDispatcher, DoctrineHelper $doctrineHelper)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function process($data, FormInterface $form, Request $request)
    {
        $event = new FormProcessEvent($form, $data);
        $this->eventDispatcher->dispatch($event, Events::BEFORE_FORM_DATA_SET);

        if ($event->isFormProcessInterrupted()) {
            return false;
        }

        $form->setData($data);

        if (in_array($request->getMethod(), ['POST', 'PUT'], true)) {
            $event = new FormProcessEvent($form, $data);
            $this->eventDispatcher->dispatch($event, Events::BEFORE_FORM_SUBMIT);

            if ($event->isFormProcessInterrupted()) {
                return false;
            }

            $this->submitPostPutRequest($form, $request);

            if ($form->isValid()) {
                $manager = $this->doctrineHelper->getEntityManager($data);

                $manager->beginTransaction();
                try {
                    $this->saveData($data, $form);
                    $manager->commit();
                } catch (\Exception $exception) {
                    $manager->rollback();
                    throw $exception;
                }

                return true;
            }
        }

        return false;
    }

    protected function saveData($data, FormInterface $form)
    {
        $manager = $this->doctrineHelper->getEntityManager($data);
        $manager->persist($data);
        $this->eventDispatcher->dispatch(new AfterFormProcessEvent($form, $data), Events::BEFORE_FLUSH);
        $manager->flush();
        $this->eventDispatcher->dispatch(new AfterFormProcessEvent($form, $data), Events::AFTER_FLUSH);
    }
}
