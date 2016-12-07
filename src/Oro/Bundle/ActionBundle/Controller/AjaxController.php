<?php

namespace Oro\Bundle\ActionBundle\Controller;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Exception\OperationNotFoundException;
use Oro\Bundle\ActionBundle\Exception\ForbiddenOperationException;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

class AjaxController extends Controller
{
    /**
     * @Route("/operation/execute/{operationName}", name="oro_action_operation_execute")
     * @AclAncestor("oro_action")
     *
     * @param Request $request
     * @param string $operationName
     * @return Response
     */
    public function executeAction(Request $request, $operationName)
    {
        $errors = new ArrayCollection();
        $code = Response::HTTP_OK;
        $message = '';

        $handler = $this->get('oro_action.handler.operation_execution');

        $data = $handler->getData($request);

        try {
            $data = $handler->execute($operationName, $data, $request, $errors);
        } catch (OperationNotFoundException $exception) {
            $code = Response::HTTP_NOT_FOUND;
        } catch (ForbiddenOperationException $exception) {
            $code = Response::HTTP_FORBIDDEN;
        } catch (\Exception $exception) {
            $code = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        if (isset($exception)) {
            $message = $exception->getMessage();
        }

        return $this->handleResponse($request, $data, $code, $message, $errors);
    }

    /**
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
     * @return array
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
}
