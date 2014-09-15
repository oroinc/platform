<?php

namespace Oro\Bundle\FormBundle\Model;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Route\Router;

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
     * @param Request $request
     * @param Session $session
     * @param Router $router
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(Request $request, Session $session, Router $router, DoctrineHelper $doctrineHelper)
    {
        $this->request = $request;
        $this->session = $session;
        $this->router = $router;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param object $entity
     * @param FormInterface $form
     * @param callable $saveAndStayRoute
     * @param callable $saveAndCloseRoute
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
     * @param FormInterface $form
     * @param object $entity
     * @return bool
     */
    protected function saveForm(FormInterface $form, $entity)
    {
        $form->setData($entity);

        if (in_array($this->request->getMethod(), array('POST', 'PUT'))) {
            $form->submit($this->request);

            if ($form->isValid()) {
                $manager = $this->doctrineHelper->getEntityManager($entity);
                $manager->persist($entity);
                $manager->flush();

                return true;
            }
        }

        return false;
    }

    /**
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
            if (is_callable($saveAndCloseRoute)) {
                $saveAndCloseRoute = call_user_func($saveAndCloseRoute, $entity);
            }
            return $this->router->redirectAfterSave($saveAndStayRoute, $saveAndCloseRoute, $entity);
        }
    }
}
