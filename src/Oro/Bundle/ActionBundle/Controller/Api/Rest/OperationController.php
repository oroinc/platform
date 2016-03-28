<?php

namespace Oro\Bundle\ActionBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Util\Codes;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\OperationManager;
use Oro\Bundle\ActionBundle\Exception\OperationNotFoundException;
use Oro\Bundle\ActionBundle\Exception\ForbiddenOperationException;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

/**
 * @Rest\RouteResource("operations")
 * @Rest\NamePrefix("oro_api_action_")
 */
class OperationController extends FOSRestController
{
    /**
     * @ApiDoc(description="Execute operation", resource=true)
     * @AclAncestor("oro_action")
     * @Rest\Get
     *
     * @param string $operationName
     * @return Response
     */
    public function executeAction($operationName)
    {
        $data = $this->getContextHelper()->getActionData();
        $errors = new ArrayCollection();

        try {
            $this->getOperationManager()->execute($operationName, $data, $errors);
        } catch (OperationNotFoundException $e) {
            return $this->handleResponse($data, Codes::HTTP_NOT_FOUND, $e->getMessage(), $errors);
        } catch (ForbiddenOperationException $e) {
            return $this->handleResponse($data, Codes::HTTP_FORBIDDEN, $e->getMessage(), $errors);
        } catch (\Exception $e) {
            return $this->handleResponse($data, Codes::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage(), $errors);
        }

        return $this->handleResponse($data);
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
     * @param ActionData $data
     * @param int $code
     * @param string $message
     * @param Collection $errorMessages
     * @return Response
     */
    protected function handleResponse(
        ActionData $data,
        $code = Codes::HTTP_OK,
        $message = '',
        Collection $errorMessages = null
    ) {
        $response = [
            'success' => $code === Codes::HTTP_OK,
            'message' => $message,
            'messages' => [],
        ];

        if ($data->getRefreshGrid() || !$response['success']) {
            $response['refreshGrid'] = $data->getRefreshGrid();
            $response['flashMessages'] = $this->get('session')->getFlashBag()->all();
        } elseif ($data->getRedirectUrl()) {
            $response['redirectUrl'] = $data->getRedirectUrl();
        } else {
            $response['reloadPage'] = true;
        }

        if (count($errorMessages)) {
            $response['messages'] = $this->prepareMessages($errorMessages->toArray());
        }

        return $this->handleView($this->view($response, $code));
    }

    /**
     * @param array $messages
     * @return array
     */
    protected function prepareMessages(array $messages)
    {
        $translator = $this->get('translator');
        $result = [];

        foreach ($messages as $message) {
            $result[] = $translator->trans($message['message'], $message['parameters']);
        }

        return $result;
    }
}
