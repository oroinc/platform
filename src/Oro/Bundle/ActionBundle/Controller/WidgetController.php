<?php

namespace Oro\Bundle\ActionBundle\Controller;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\OperationManager;

class WidgetController extends Controller
{
    /**
     * @Route("/buttons", name="oro_action_widget_buttons")
     * @Template()
     *
     * @param Request $request
     * @return array
     */
    public function buttonsAction(Request $request)
    {
        $contextHelper = $this->getContextHelper();
        $applicationsHelper = $this->getApplicationsHelper();

        return [
            'operations' => $this->getOperationManager()->getOperations(),
            'context' => $contextHelper->getContext(),
            'actionData' => $contextHelper->getActionData(),
            'dialogRoute' => $applicationsHelper->getDialogRoute(),
            'executionRoute' => $applicationsHelper->getExecutionRoute(),
            'fromUrl' => $request->get('fromUrl'),
        ];
    }

    /**
     * @Route("/form/{operationName}", name="oro_action_widget_form")
     *
     * @param Request $request
     * @param string $operationName
     * @return Response
     */
    public function formAction(Request $request, $operationName)
    {
        $data = $this->getContextHelper()->getActionData();

        $params = [
            '_wid' => $request->get('_wid'),
            'fromUrl' => $request->get('fromUrl'),
            'operation' => $this->getOperationManager()->getOperation($operationName, $data),
            'actionData' => $data,
            'errors' => new ArrayCollection(),
            'messages' => [],
        ];

        try {
            /** @var Form $form */
            $form = $this->get('oro_action.form_manager')->getOperationForm($operationName, $data);

            $data['form'] = $form;

            $form->handleRequest($request);

            if ($form->isValid()) {
                $this->getOperationManager()->execute($operationName, $data, $params['errors']);

                $params['response'] = $this->getResponse($data);

                if ($this->hasRedirect($params)) {
                    return $this->redirect($params['response']['redirectUrl']);
                }
            }

        } catch (\Exception $e) {
            $params = array_merge($params, $this->getErrorResponse(
                $params,
                $this->getErrorMessages($e, $params['errors'])
            ));
        }

        if (isset($form)) {
            $params['form'] = $form->createView();
        }

        $params['context'] = $data->getValues();

        return $this->render($this->getOperationManager()->getFrontendTemplate($operationName), $params);
    }

    /**
     * @param array $params
     * @return bool
     */
    protected function hasRedirect(array $params)
    {
        return empty($params['_wid']) && !empty($params['response']['redirectUrl']);
    }

    /**
     * @return OperationManager
     */
    protected function getOperationManager()
    {
        return $this->get('oro_action.operation_manager');
    }

    /**
     * @return ContextHelper
     */
    protected function getContextHelper()
    {
        return $this->get('oro_action.helper.context');
    }

    /**
     * @return ApplicationsHelper
     */
    protected function getApplicationsHelper()
    {
        return $this->get('oro_action.helper.applications');
    }

    /**
     * @param \Exception $e
     * @param Collection $errors
     * @return ArrayCollection
     */
    protected function getErrorMessages(\Exception $e, Collection $errors = null)
    {
        $messages = new ArrayCollection();

        if (!$errors->count()) {
            $messages->add(['message' => $e->getMessage(), 'parameters' => []]);
        } else {
            foreach ($errors as $key => $error) {
                $messages->set($key, [
                    'message' => sprintf('%s: %s', $e->getMessage(), $error['message']),
                    'parameters' => $error['parameters'],
                ]);
            }
        }

        return $messages;
    }

    /**
     * @param array $params
     * @param Collection $messages
     * @return array
     */
    protected function getErrorResponse(array $params, Collection $messages)
    {
        /* @var $flashBag FlashBagInterface */
        $flashBag = $this->get('session')->getFlashBag();

        if (!empty($params['_wid'])) {
            return [
                'errors' => $messages,
                'messages' => $flashBag->all(),
            ];
        }

        /* @var $translator TranslatorInterface */
        $translator = $this->get('translator');

        foreach ($messages as $message) {
            $flashBag->add('error', $translator->trans($message['message'], $message['parameters']));
        }

        return [];
    }

    /**
     * @param ActionData $context
     * @return array
     */
    protected function getResponse(ActionData $context)
    {
        $response = ['success' => true];

        if ($context->getRedirectUrl()) {
            $response['redirectUrl'] = $context->getRedirectUrl();
        } elseif ($context->getRefreshGrid()) {
            $response['refreshGrid'] = $context->getRefreshGrid();
            $response['flashMessages'] = $this->get('session')->getFlashBag()->all();
        }

        return $response;
    }
}
