<?php

namespace Oro\Bundle\FormBundle\Model;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Form\Handler\FormHandler;
use Oro\Bundle\UIBundle\Route\Router;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * @deprecated since v2.1.0 use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade (oro_form.update_handler) instead
 */
class UpdateHandler
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

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
     * @var FormHandler
     */
    protected $formHandler;

    /**
     * @param RequestStack $requestStack
     * @param Session $session
     * @param Router $router
     * @param DoctrineHelper $doctrineHelper
     * @param FormHandler $formHandler
     */
    public function __construct(
        RequestStack $requestStack,
        Session $session,
        Router $router,
        DoctrineHelper $doctrineHelper,
        FormHandler $formHandler
    ) {
        $this->requestStack = $requestStack;
        $this->session = $session;
        $this->router = $router;
        $this->doctrineHelper = $doctrineHelper;
        $this->formHandler = $formHandler;
    }

    /**
     * @deprecated since 1.10 and will be removed after 1.12. Use update method instead
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
     * Handles update action of controller used to create or update entity on separate page or widget dialog.
     *
     * @param object $data Data of form
     * @param FormInterface $form Form instance
     * @param string $saveMessage Message added to session flash bag in case if form will be saved successfully
     *               and if form is not submitted from widget.
     * @param null|callable $formHandler Callback to handle form, by default method saveForm used.
     * @param callable|null $resultCallback
     * @return array|RedirectResponse Returns an array
     *                                  if form wasn't successfully submitted
     *                                  or when request method is not PUT and POST,
     *                                  or if form was submitted from widget dialog,
     *                                returns RedirectResponse
     *                                  if form was successfully submitted from create/update page
     * @throws \InvalidArgumentException
     */
    public function update(
        $data,
        FormInterface $form,
        $saveMessage,
        $formHandler = null,
        $resultCallback = null
    ) {
        if ($formHandler) {
            if (is_object($formHandler) && method_exists($formHandler, 'process')) {
                if ($formHandler->process($data)) {
                    return $this->constructResponse($form, $data, $saveMessage, $resultCallback);
                }
            } else {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Argument $formHandler should be an object with method "process", %s given.',
                        is_object($formHandler) ? get_class($formHandler) : gettype($formHandler)
                    )
                );
            }
        } elseif ($this->saveForm($form, $data)) {
            return $this->constructResponse($form, $data, $saveMessage, $resultCallback);
        }

        return $this->getResult($data, $form, $resultCallback);
    }

    /**
     * @param FormInterface $form
     * @param object $data
     * @return bool
     * @throws \Exception
     */
    protected function saveForm(FormInterface $form, $data)
    {
        return $this->formHandler->process($data, $form, $this->getCurrentRequest());
    }

    /**
     * @deprecated since 1.10 and will be removed after 1.12
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
        $request = $this->getCurrentRequest();
        if ($request->get('_wid')) {
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
     * @param string $saveMessage
     * @param null $resultCallback
     *
     * @return array|RedirectResponse
     */
    protected function constructResponse(FormInterface $form, $entity, $saveMessage, $resultCallback = null)
    {
        $request = $this->getCurrentRequest();
        if ($request->get('_wid')) {
            $result = $this->getResult($entity, $form, $resultCallback);
            $result['savedId'] = $this->doctrineHelper->getSingleEntityIdentifier($entity);

            return $result;
        } else {
            $this->session->getFlashBag()->add('success', $saveMessage);

            return $this->router->redirect($entity);
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
        $request = $this->getCurrentRequest();
        if (is_callable($resultCallback)) {
            $result = call_user_func($resultCallback, $entity, $form, $request);
        } else {
            $result = [
                'form' => $form->createView()
            ];
        }
        if (!array_key_exists('entity', $result)) {
            $result['entity'] = $entity;
        }
        $result['isWidgetContext'] = (bool)$request->get('_wid', false);

        return $result;
    }

    /**
     * @param array $routeData
     * @return array
     */
    protected function addQueryParameters(array $routeData)
    {
        $request = $this->getCurrentRequest();
        $queryParts = $request->query->all();
        if ($queryParts) {
            if (!isset($routeData['parameters'])) {
                $routeData['parameters'] = [];
            }
            $routeData['parameters'] = array_merge($queryParts, $routeData['parameters']);
        }

        return $routeData;
    }

    /**
     * @return Request
     */
    protected function getCurrentRequest()
    {
        return $this->requestStack->getCurrentRequest();
    }
}
