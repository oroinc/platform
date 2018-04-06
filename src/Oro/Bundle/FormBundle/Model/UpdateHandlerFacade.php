<?php

namespace Oro\Bundle\FormBundle\Model;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;
use Oro\Bundle\UIBundle\Route\Router;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

class UpdateHandlerFacade
{
    /** @var UpdateFactory */
    private $updateFactory;

    /** @var RequestStack */
    private $requestStack;

    /** @var Session */
    private $session;

    /** @var Router */
    private $router;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param RequestStack $requestStack
     * @param Session $session
     * @param Router $router
     * @param DoctrineHelper $doctrineHelper
     * @param UpdateFactory $updateFactory
     */
    public function __construct(
        RequestStack $requestStack,
        Session $session,
        Router $router,
        DoctrineHelper $doctrineHelper,
        UpdateFactory $updateFactory
    ) {
        $this->requestStack = $requestStack;
        $this->session = $session;
        $this->router = $router;
        $this->doctrineHelper = $doctrineHelper;
        $this->updateFactory = $updateFactory;
    }

    /**
     * Handles update action of controller used to create or update entity on separate page or widget dialog.
     * Has reusable segments such as $formHandler and $resultProvider to be able use your custom services by alias
     *
     * @param string|object $data Data of form or FQCN
     * @param string|FormInterface $form Form instance or form_type name
     * @param string $saveMessage Message added to session flash bag in case if form will be saved successfully
     *      and if form is not submitted from widget.
     * @param Request|null $request optional Request instance otherwise will be used current request from RequestStack
     * @param FormHandlerInterface|string|callable|null $formHandler to handle form (string is an alias for registered)
     *      string - an alias of registered by tag 'oro_form.form.handler' FormHandlerInterface implementation service
     *      callable - a callback to perform handling (see FormHandlerInterface::process() for arguments)
     *      FormHandlerInterface - an instance of handler itself
     *      null - use 'default' registered handler
     * @param FormTemplateDataProviderInterface|string|callable|null $resultProvider to provide template data
     *      string - an alias of registered by tag 'oro_form.form_template_data_provider' service
     *      callable - callback to provide data (see FormTemplateDataProviderInterface::getData() for arguments)
     *      FormTemplateDataProviderInterface - an instance of provider itself
     *      null - use 'default' registered provider (usually it returns ['form' => FormView $instance])
     *
     * @return array|RedirectResponse
     *      returns an array
     *          if form was not successfully submitted
     *          or when request method is not PUT and POST,
     *          or if form was submitted from widget dialog,
     *      returns RedirectResponse
     *          if form was successfully submitted from create/update page
     */
    public function update(
        $data,
        $form,
        $saveMessage,
        Request $request = null,
        $formHandler = null,
        $resultProvider = null
    ) {
        $update = $this->updateFactory->createUpdate($data, $form, $formHandler, $resultProvider);

        $request = $request ?: $this->getCurrentRequest();

        if ($update->handle($request)) {
            return $this->constructResponse($update, $request, $saveMessage);
        }

        return $this->getResult($update, $request);
    }

    /**
     * @param UpdateInterface $update
     * @param Request $request
     * @param string $saveMessage
     *
     * @return array|RedirectResponse
     */
    protected function constructResponse(UpdateInterface $update, Request $request, $saveMessage)
    {
        $entity = $update->getFormData();
        if ($request->get('_wid')) {
            $result = $this->getResult($update, $request);
            $result['savedId'] = $this->doctrineHelper->getSingleEntityIdentifier($entity);

            return $result;
        } else {
            $this->session->getFlashBag()->add('success', $saveMessage);

            return $this->router->redirect($entity);
        }
    }

    /**
     * @param UpdateInterface $update
     * @param Request $request
     *
     * @return array
     */
    protected function getResult(UpdateInterface $update, Request $request)
    {
        $result = $update->getTemplateData($request);

        if (!array_key_exists('entity', $result)) {
            $result['entity'] = $update->getFormData();
        }
        $result['isWidgetContext'] = (bool)$request->get('_wid', false);

        return $result;
    }

    /**
     * @return Request
     */
    protected function getCurrentRequest()
    {
        return $this->requestStack->getCurrentRequest();
    }
}
