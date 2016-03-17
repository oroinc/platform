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

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\OperationManager;
use Oro\Bundle\ActionBundle\Exception\ActionNotFoundException;
use Oro\Bundle\ActionBundle\Exception\ForbiddenActionException;
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

        try {
            $data = $this->getOperationManager()->executeByContext($actionName, null, $errors);
        } catch (ActionNotFoundException $e) {
            return $this->handleError($e->getMessage(), Codes::HTTP_NOT_FOUND, $errors);
        } catch (ForbiddenActionException $e) {
            return $this->handleError($e->getMessage(), Codes::HTTP_FORBIDDEN, $errors);
        } catch (\Exception $e) {
            return $this->handleError($e->getMessage(), Codes::HTTP_INTERNAL_SERVER_ERROR, $errors);
        }

        return $this->handleView(
            $this->view($this->getResponse($data), Codes::HTTP_OK)
        );
    }

    /**
     * @return OperationManager
     */
    protected function getOperationManager()
    {
        return $this->get('oro_action.operation_manager');
    }

    /**
     * @param string $message
     * @param int $code
     * @param Collection $errorMessages
     * @return Response
     */
    protected function handleError($message, $code, Collection $errorMessages)
    {
        $messages = [];

        if (count($errorMessages)) {
            $translator = $this->get('translator');

            foreach ($errorMessages as $errorMessage) {
                $messages[] = $translator->trans($errorMessage['message'], $errorMessage['parameters']);
            }
        }

        return $this->handleView(
            $this->view($this->formatErrorResponse($message, $messages), $code)
        );
    }

    /**
     * @param string $message
     * @param array $messages
     * @return array
     */
    protected function formatErrorResponse($message, array $messages = [])
    {
        return ['message' => $message, 'messages' => $messages];
    }

    /**
     * @param ActionData $data
     * @return array
     */
    protected function getResponse(ActionData $data)
    {
        /* @var $session Session */
        $session = $this->get('session');

        $response = [];
        if ($data->getRedirectUrl()) {
            $response['redirectUrl'] = $data->getRedirectUrl();
        } elseif ($data->getRefreshGrid()) {
            $response['refreshGrid'] = $data->getRefreshGrid();
            $response['flashMessages'] = $session->getFlashBag()->all();
        }

        return $response;
    }
}
