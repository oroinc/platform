<?php

namespace Oro\Bundle\ActionBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Handler\ExecuteOperationResult;
use Oro\Bundle\ActionBundle\Model\OperationRegistry;

class AjaxController extends Controller
{
    /**
     * @Route("/operation/execute/{operationName}", name="oro_action_operation_execute")
     * @AclAncestor("oro_action")
     * @Method({"POST"})
     *
     * @param Request $request
     * @param string  $operationName
     *
     * @return Response
     */
    public function executeAction(Request $request, $operationName)
    {
        $operation = $this->get('oro_action.operation_registry')->findByName($operationName);
        if (!$operation instanceof Operation) {
            $message = sprintf('Operation with name "%s" not found', $operationName);

            $routeName = $request->get('route');
            if (null !== $routeName && !$request->isXmlHttpRequest()) {
                return $this->handleFailedNonAjaxResponse($message, $routeName);
            }

            return new JsonResponse(
                [
                    'message' => $message,
                    'success' => false
                ],
                Response::HTTP_NOT_FOUND
            );
        }
        $executionResult = $this->get('oro_action.handler.execute_operation')->process($operation);

        return $this->handleExecutionResult($executionResult, $request);
    }

    /**
     * @param ExecuteOperationResult $result
     * @param Request                $request
     *
     * @return Response
     * @throws \InvalidArgumentException
     */
    protected function handleExecutionResult(ExecuteOperationResult $result, Request $request)
    {
        $validationErrors = $result->getValidationErrors();
        $actionData = $result->getActionData();
        $success = $result->isSuccess();
        $response   = [
            'success'    => $success,
            'message'    => $result->getExceptionMessage(),
            'messages'   => $this->prepareMessages($validationErrors),
        ];
        if (!$success) {
            $response['refreshGrid'] = $actionData->getRefreshGrid();
            $routeName = $request->get('route');
            if (null !== $routeName && !$request->isXmlHttpRequest()) {
                return $this->handleFailedNonAjaxResponse($response['message'], $routeName);
            }
        } else {
            if ($actionData->getRefreshGrid()) {
                $response['refreshGrid'] = $actionData->getRefreshGrid();
                $response['flashMessages'] = $this->get('session')->getFlashBag()->all();
            } elseif ($actionData->getRedirectUrl()) {
                if ($request->isXmlHttpRequest()) {
                    $response['redirectUrl'] = $actionData->getRedirectUrl();
                } else {
                    return $this->redirect($actionData->getRedirectUrl());
                }
            }
        }

        return new JsonResponse($response, $result->getCode());
    }

    /**
     * @deprecated
     *
     * @param Request $request
     * @param ActionData $data
     * @param int $code
     * @param string $message
     * @param Collection $errorMessages
     * @return Response
     */
    protected function handleResponse(Request $request, ActionData $data, $code, $message, Collection $errorMessages)
    {
        $response = [
            'success' => $code === Response::HTTP_OK,
            'message' => $message,
            'messages' => $this->prepareMessages($errorMessages)
        ];

        // handle redirect for failure response on non ajax requests
        if (!$request->isXmlHttpRequest() && !$response['success'] && null !== ($routeName = $request->get('route'))) {
            $this->get('session')->getFlashBag()->add('error', $response['message']);

            return $this->redirect($this->generateUrl($routeName));
        }

        if ($data->getRefreshGrid() || !$response['success']) {
            $response['refreshGrid'] = $data->getRefreshGrid();
            $response['flashMessages'] = $this->get('session')->getFlashBag()->all();
        } elseif ($data->getRedirectUrl()) {
            if ($request->isXmlHttpRequest()) {
                $response['redirectUrl'] = $data->getRedirectUrl();
            } else {
                return $this->redirect($data->getRedirectUrl());
            }
        }

        return new JsonResponse($response, $code);
    }

    /**
     * @param Collection $messages
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function prepareMessages(Collection $messages)
    {
        $translator = $this->get('translator');
        $result = [];
        foreach ($messages as $message) {
            $result[] = $translator->trans($message['message'], $message['parameters']);
        }

        return $result;
    }

    /**
     * Handle failed response non ajax requests
     *
     * @param string $message
     * @param string $routeName
     *
     * @return RedirectResponse
     */
    protected function handleFailedNonAjaxResponse($message, $routeName)
    {
        $this->get('session')->getFlashBag()->add('error', $message);

        return $this->redirect($this->generateUrl($routeName));
    }

    /**
     * @return OperationRegistry
     */
    protected function getOperationRegistry()
    {
        return $this->get('oro_action.operation_registry');
    }

    /**
     * @return ContextHelper
     */
    protected function getContextHelper()
    {
        return $this->get('oro_action.helper.context');
    }
}
