<?php

namespace Oro\Bundle\ActionBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Util\Codes;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\OperationManager;
use Oro\Bundle\ActionBundle\Exception\ActionNotFoundException;
use Oro\Bundle\ActionBundle\Exception\ForbiddenOperationException;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

/**
 * @Rest\RouteResource("actions")
 * @Rest\NamePrefix("oro_api_action_")
 */
class OperationController extends FOSRestController
{
    /**
     * @ApiDoc(description="Execute action", resource=true)
     * @AclAncestor("oro_action")
     * @Rest\Get
     *
     * @param string $actionName
     * @return Response
     */
    public function executeAction($actionName)
    {
        $errors = new ArrayCollection();

        $data = $this->getContextHelper()->getActionData();

        try {
            $this->getOperationManager()->execute($actionName, $data, $errors);
        } catch (ActionNotFoundException $e) {
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
     */
    protected function handleResponse(
        ActionData $data,
        $code = Codes::HTTP_OK,
        $message = '',
        Collection $errorMessages = null
    ) {
        /* @var $session Session */
        $session = $this->get('session');

        $response = [
            'success' => $code === Codes::HTTP_OK,
            'message' => $message,
            'messages' => [],
        ];

        if ($data->getRefreshGrid() || !$response['success']) {
            $response['refreshGrid'] = $data->getRefreshGrid();
            $response['flashMessages'] = $session->getFlashBag()->all();
        } elseif ($data->getRedirectUrl()) {
            $response['redirectUrl'] = $data->getRedirectUrl();
        } else {
            $response['reloadPage'] = true;
        }

        if (count($errorMessages)) {
            /* @var $translator TranslatorInterface */
            $translator = $this->get('translator');

            foreach ($errorMessages as $errorMessage) {
                $response['messages'][] = $translator->trans($errorMessage['message'], $errorMessage['parameters']);
            }
        }

        return $this->handleView($this->view($response, $code));
    }
}
