<?php

namespace Oro\Bundle\FormBundle\Model;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Route\Router;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\FormBundle\Event\FormHandler\Events;
use Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent;

class UpdateHandler
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param Request $request
     * @param Session $session
     * @param Router $router
     * @param DoctrineHelper $doctrineHelper
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        Request $request,
        Session $session,
        Router $router,
        DoctrineHelper $doctrineHelper,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->request = $request;
        $this->session = $session;
        $this->router = $router;
        $this->doctrineHelper = $doctrineHelper;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @deprecated since 1.9 and will be removed after 1.11. Use handleEntityUpdateRequest method instead
     *
     * @param object $entity
     * @param FormInterface $form
     * @param array|callable $saveAndStayRoute
     * @param array|callable $saveAndCloseRoute
     * @param string $saveMessage
     * @param null|object $formHandler
     * @param callable|null $resultCallback
     * @return array|RedirectResponse
     */
    public function handleUpdate(
        $entity,
        FormInterface $form,
        $saveAndStayRoute,
        $saveAndCloseRoute,
        $saveMessage,
        $formHandler = null,
        $resultCallback = null
    ) {
        if ($formHandler) {
            if (method_exists($formHandler, 'process') && $formHandler->process($entity)) {
                return $this->processSave(
                    $form,
                    $entity,
                    $saveAndStayRoute,
                    $saveAndCloseRoute,
                    $saveMessage,
                    $resultCallback
                );
            }
        } elseif ($this->saveForm($form, $entity)) {
            return $this->processSave(
                $form,
                $entity,
                $saveAndStayRoute,
                $saveAndCloseRoute,
                $saveMessage,
                $resultCallback
            );
        }

        return $this->getResult($entity, $form, $resultCallback);
    }

    /**
     * @param object $entity
     * @param FormInterface $form
     * @param string $saveMessage
     * @param null|object $formHandler
     * @param callable|null $resultCallback
     * @return array|RedirectResponse
     */
    public function handleEntityUpdateRequest(
        $entity,
        FormInterface $form,
        $saveMessage,
        $formHandler = null,
        $resultCallback = null
    ) {
        if ($formHandler) {
            if (method_exists($formHandler, 'process') && $formHandler->process($entity)) {
                return $this->constructResponse($form, $entity, $saveMessage, $resultCallback);
            }
        } elseif ($this->saveForm($form, $entity)) {
            return $this->constructResponse($form, $entity, $saveMessage, $resultCallback);
        }

        return $this->getResult($entity, $form, $resultCallback);
    }

    /**
     * @param FormInterface $form
     * @param object $entity
     * @return bool
     * @throws \Exception
     */
    protected function saveForm(FormInterface $form, $entity)
    {
        $event = new FormProcessEvent($form, $entity);
        $this->eventDispatcher->dispatch(Events::BEFORE_FORM_DATA_SET, $event);

        if ($event->isFormProcessInterrupted()) {
            return false;
        }

        $form->setData($entity);

        if (in_array($this->request->getMethod(), array('POST', 'PUT'))) {
            $event = new FormProcessEvent($form, $entity);
            $this->eventDispatcher->dispatch(Events::BEFORE_FORM_SUBMIT, $event);

            if ($event->isFormProcessInterrupted()) {
                return false;
            }

            $form->submit($this->request);

            if ($form->isValid()) {
                $manager = $this->doctrineHelper->getEntityManager($entity);

                $manager->beginTransaction();
                try {
                    $manager->persist($entity);
                    $this->eventDispatcher->dispatch(Events::BEFORE_FLUSH, new AfterFormProcessEvent($form, $entity));
                    $manager->flush();
                    $this->eventDispatcher->dispatch(Events::AFTER_FLUSH, new AfterFormProcessEvent($form, $entity));
                    $manager->commit();
                } catch (\Exception $e) {
                    $manager->rollback();
                    throw $e;
                }

                return true;
            }
        }

        return false;
    }

    /**
     * @deprecated since 1.9 and will be removed after 1.11
     *
     * @param FormInterface $form
     * @param object $entity
     * @param array|callable $saveAndStayRoute
     * @param array|callable $saveAndCloseRoute
     * @param string $saveMessage
     * @param callback|null $resultCallback
     * @return array|RedirectResponse
     */
    protected function processSave(
        FormInterface $form,
        $entity,
        $saveAndStayRoute,
        $saveAndCloseRoute,
        $saveMessage,
        $resultCallback = null
    ) {
        if ($this->request->get('_wid')) {
            $result = $this->getResult($entity, $form, $resultCallback);
            $result['savedId'] = $this->doctrineHelper->getSingleEntityIdentifier($entity);

            return $result;
        } else {
            $this->session->getFlashBag()->add('success', $saveMessage);

            if (is_callable($saveAndStayRoute)) {
                $saveAndStayRoute = call_user_func($saveAndStayRoute, $entity);
            }
            $saveAndStayRoute = $this->addQueryParameters($saveAndStayRoute);

            if (is_callable($saveAndCloseRoute)) {
                $saveAndCloseRoute = call_user_func($saveAndCloseRoute, $entity);
            }
            $saveAndCloseRoute = $this->addQueryParameters($saveAndCloseRoute);

            return $this->router->redirectAfterSave($saveAndStayRoute, $saveAndCloseRoute, $entity);
        }
    }

    /**
     * @param FormInterface $form
     * @param object $entity
     * @param               $saveMessage
     * @param null          $resultCallback
     *
     * @return array|RedirectResponse
     */
    protected function constructResponse(FormInterface $form, $entity, $saveMessage, $resultCallback = null)
    {
        if ($this->request->get('_wid')) {
            $result = $this->getResult($entity, $form, $resultCallback);
            $result['savedId'] = $this->doctrineHelper->getSingleEntityIdentifier($entity);

            return $result;
        } else {
            $this->session->getFlashBag()->add('success', $saveMessage);

            return $this->router->redirectToAfterSaveAction($entity);
        }
    }

    /**
     * @param object $entity
     * @param FormInterface $form
     * @param callable|null $resultCallback
     * @return array
     */
    protected function getResult($entity, FormInterface $form, $resultCallback = null)
    {
        if (is_callable($resultCallback)) {
            $result = call_user_func($resultCallback, $entity, $form, $this->request);
        } else {
            $result = array(
                'form' => $form->createView()
            );
        }
        if (!array_key_exists('entity', $result)) {
            $result['entity'] = $entity;
        }
        $result['isWidgetContext'] = (bool)$this->request->get('_wid', false);

        return $result;
    }

    /**
     * @param array $routeData
     * @return array
     */
    protected function addQueryParameters(array $routeData)
    {
        $queryParts = $this->request->query->all();
        if ($queryParts) {
            if (!isset($routeData['parameters'])) {
                $routeData['parameters'] = [];
            }
            $routeData['parameters'] = array_merge($queryParts, $routeData['parameters']);
        }

        return $routeData;
    }
}
