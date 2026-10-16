<?php

namespace Oro\Bundle\FormBundle\Form\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\FormBundle\Event\FormHandler\Events;
use Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handler for the back-office form with dynamic structure based on ajax requests.
 */
class FormWithAjaxReloadHandler implements FormHandlerInterface
{
    use RequestHandlerTrait;

    public const string WITHOUT_SAVING_KEY = 'reloadWithoutSaving';

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @throws \Exception
     */
    #[\Override]
    public function process($data, FormInterface $form, Request $request): bool
    {
        $event = new FormProcessEvent($form, $data);
        $this->eventDispatcher->dispatch($event, Events::BEFORE_FORM_DATA_SET);
        if ($event->isFormProcessInterrupted()) {
            return false;
        }

        $form->setData($data);

        if (!$this->isApplicable($request)) {
            return false;
        }

        $event = new FormProcessEvent($form, $data);
        $this->eventDispatcher->dispatch($event, Events::BEFORE_FORM_SUBMIT);
        if ($event->isFormProcessInterrupted()) {
            return false;
        }

        $this->submitPostPutRequest($form, $request);

        if (null !== $request->get(self::WITHOUT_SAVING_KEY)) {
            $form->clearErrors(true);

            return false;
        }

        if (!$form->isValid()) {
            return false;
        }

        $this->saveData($data, $form);

        return true;
    }

    /**
     * @throws \Exception
     */
    private function saveData(object $data, FormInterface $form): void
    {
        $entityManager = $this->doctrine->getManagerForClass($data::class);
        assert($entityManager instanceof EntityManagerInterface);

        $entityManager->beginTransaction();
        try {
            $entityManager->persist($data);
            $this->eventDispatcher->dispatch(new AfterFormProcessEvent($form, $data), Events::BEFORE_FLUSH);
            $entityManager->flush();
            $this->eventDispatcher->dispatch(new AfterFormProcessEvent($form, $data), Events::AFTER_FLUSH);
            $entityManager->commit();
        } catch (\Exception $exception) {
            $entityManager->rollback();

            throw $exception;
        }
    }

    private function isApplicable(Request $request): bool
    {
        return \in_array($request->getMethod(), [Request::METHOD_POST, Request::METHOD_PUT], true);
    }
}
