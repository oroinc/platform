<?php

namespace Oro\Bundle\WorkflowBundle\Controller\Api\Rest;

use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

// Annotations import
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

// Exceptions
use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException;
use Oro\Bundle\WorkflowBundle\Exception\ForbiddenTransitionException;
use Oro\Bundle\WorkflowBundle\Exception\UnknownAttributeException;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException;

/**
 * @Rest\NamePrefix("oro_api_workflow_")
 */
class WorkflowController extends FOSRestController
{
    /**
     * Returns:
     * - HTTP_OK (200) response: array('workflowItem' => array('id' => int, 'result' => array(...), ...))
     * - HTTP_BAD_REQUEST (400) response: array('message' => errorMessageString)
     * - HTTP_FORBIDDEN (403) response: array('message' => errorMessageString)
     * - HTTP_NOT_FOUND (404) response: array('message' => errorMessageString)
     * - HTTP_INTERNAL_SERVER_ERROR (500) response: array('message' => errorMessageString)
     *
     * @Rest\Get(
     *      "/api/rest/{version}/workflow/start/{workflowName}/{transitionName}",
     *      requirements={"version"="latest|v1"},
     *      defaults={"version"="latest", "_format"="json"}
     * )
     * @ApiDoc(description="Start workflow for entity from transition", resource=true)
     * @AclAncestor("oro_workflow")
     *
     * @param string $workflowName
     * @param string $transitionName
     * @param Request $request
     * @return Response
     */
    public function startAction($workflowName, $transitionName, Request $request)
    {
        try {
            /** @var WorkflowManager $workflowManager */
            $workflowManager = $this->get('oro_workflow.manager');

            $entityId = $request->get('entityId', 0);
            $data = $request->get('data');
            $dataArray = [];
            if ($data) {
                $serializer = $this->get('oro_workflow.serializer.data.serializer');
                $serializer->setWorkflowName($workflowName);
                /** @var WorkflowData $data */
                $data = $serializer->deserialize(
                    $data,
                    WorkflowData::class,
                    'json'
                );
                $dataArray = $data->getValues();
            }

            $workflow = $workflowManager->getWorkflow($workflowName);
            $entityClass = $workflow->getDefinition()->getRelatedEntity();
            $entity = $this->getEntityReference($entityClass, $entityId);

            $workflowItem = $workflowManager->startWorkflow($workflow, $entity, $transitionName, $dataArray);
        } catch (HttpException $e) {
            return $this->handleError($e->getMessage(), $e->getStatusCode());
        } catch (WorkflowNotFoundException $e) {
            return $this->handleError($e->getMessage(), Codes::HTTP_NOT_FOUND);
        } catch (UnknownAttributeException $e) {
            return $this->handleError($e->getMessage(), Codes::HTTP_BAD_REQUEST);
        } catch (InvalidTransitionException $e) {
            return $this->handleError($e->getMessage(), Codes::HTTP_BAD_REQUEST);
        } catch (ForbiddenTransitionException $e) {
            return $this->handleError($e->getMessage(), Codes::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return $this->handleError($e->getMessage(), Codes::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->handleView(
            $this->view(
                array(
                    'workflowItem' => $workflowItem
                ),
                Codes::HTTP_OK
            )
        );
    }

    /**
     * Try to get reference to entity
     *
     * @param string $entityClass
     * @param mixed $entityId
     * @throws BadRequestHttpException
     * @return mixed
     */
    protected function getEntityReference($entityClass, $entityId)
    {
        /** @var DoctrineHelper $doctrineHelper */
        $doctrineHelper = $this->get('oro_entity.doctrine_helper');
        try {
            if ($entityId) {
                $entity = $doctrineHelper->getEntityReference($entityClass, $entityId);
            } else {
                $entity = $doctrineHelper->createEntityInstance($entityClass);
            }
        } catch (NotManageableEntityException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }

        return $entity;
    }

    /**
     * Returns:
     * - HTTP_OK (200) response: array('workflowItem' => array('id' => int, 'result' => array(...), ...))
     * - HTTP_BAD_REQUEST (400) response: array('message' => errorMessageString)
     * - HTTP_FORBIDDEN (403) response: array('message' => errorMessageString)
     * - HTTP_NOT_FOUND (404) response: array('message' => errorMessageString)
     * - HTTP_INTERNAL_SERVER_ERROR (500) response: array('message' => errorMessageString)
     *
     * @Rest\Get(
     *      "/api/rest/{version}/workflow/transit/{workflowItemId}/{transitionName}",
     *      requirements={"version"="latest|v1", "workflowItemId"="\d+"},
     *      defaults={"version"="latest", "_format"="json"}
     * )
     * @ParamConverter("workflowItem", options={"id"="workflowItemId"})
     * @ApiDoc(description="Perform transition for workflow item", resource=true)
     * @AclAncestor("oro_workflow")
     *
     * @param WorkflowItem $workflowItem
     * @param string $transitionName
     * @return Response
     */
    public function transitAction(WorkflowItem $workflowItem, $transitionName)
    {
        try {
            $this->get('oro_workflow.manager')->transit($workflowItem, $transitionName);
        } catch (WorkflowNotFoundException $e) {
            return $this->handleError($e->getMessage(), Codes::HTTP_NOT_FOUND);
        } catch (InvalidTransitionException $e) {
            return $this->handleError($e->getMessage(), Codes::HTTP_BAD_REQUEST);
        } catch (ForbiddenTransitionException $e) {
            return $this->handleError($e->getMessage(), Codes::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return $this->handleError($e->getMessage(), Codes::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->handleView(
            $this->view(
                array(
                    'workflowItem' => $workflowItem
                ),
                Codes::HTTP_OK
            )
        );
    }

    /**
     * Returns
     * - HTTP_OK (200) response: array('workflowItem' => array('id' => int, 'result' => array(...), ...))
     *
     * @Rest\Get(
     *      "/api/rest/{version}/workflow/{workflowItemId}",
     *      requirements={"version"="latest|v1", "workflowItemId"="\d+"},
     *      defaults={"version"="latest", "_format"="json"}
     * )
     * @ParamConverter("workflowItem", options={"id"="workflowItemId"})
     * @ApiDoc(description="Get workflow item", resource=true)
     * @AclAncestor("oro_workflow")
     *
     * @param WorkflowItem $workflowItem
     * @return Response
     */
    public function getAction(WorkflowItem $workflowItem)
    {
        return $this->handleView(
            $this->view(
                array(
                    'workflowItem' => $workflowItem
                ),
                Codes::HTTP_OK
            )
        );
    }

    /**
     * Delete workflow item
     *
     * Returns
     * - HTTP_NO_CONTENT (204)
     *
     * @Rest\Delete(
     *      "/api/rest/{version}/workflow/{workflowItemId}",
     *      requirements={"version"="latest|v1", "workflowItemId"="\d+"},
     *      defaults={"version"="latest", "_format"="json"}
     * )
     * @ParamConverter("workflowItem", options={"id"="workflowItemId"})
     * @ApiDoc(description="Delete workflow item", resource=true)
     * @AclAncestor("oro_workflow")
     *
     * @param WorkflowItem $workflowItem
     * @return Response
     */
    public function deleteAction(WorkflowItem $workflowItem)
    {
        $this->get('oro_workflow.manager')->resetWorkflowItem($workflowItem);
        return $this->handleView($this->view(null, Codes::HTTP_NO_CONTENT));
    }

    /**
     * Activate workflow
     *
     * Returns
     * - HTTP_OK (200)
     *
     * @Rest\Get(
     *      "/api/rest/{version}/workflow/activate/{workflowDefinition}",
     *      requirements={"version"="latest|v1"},
     *      defaults={"version"="latest", "_format"="json"}
     * )
     * @ApiDoc(description="Activate workflow", resource=true)
     * @AclAncestor("oro_workflow")
     *
     * @param WorkflowDefinition $workflowDefinition
     * @return Response
     */
    public function activateAction(WorkflowDefinition $workflowDefinition)
    {
        $workflowManager = $this->get('oro_workflow.manager');

        $workflowManager->resetWorkflowData($workflowDefinition);
        $workflowManager->activateWorkflow($workflowDefinition);

        return $this->handleView(
            $this->view(
                array(
                    'successful' => true,
                    'message' => $this->get('translator')->trans('Workflow activated')
                ),
                Codes::HTTP_OK
            )
        );
    }

    /**
     * Deactivate workflow
     *
     * Returns
     * - HTTP_OK (204)
     *
     * @Rest\Get(
     *      "/api/rest/{version}/workflow/deactivate/{workflowDefinition}",
     *      requirements={"version"="latest|v1"},
     *      defaults={"version"="latest", "_format"="json"}
     * )
     * @ApiDoc(description="Deactivate workflow", resource=true)
     * @AclAncestor("oro_workflow")
     *
     * @param WorkflowDefinition $workflowDefinition
     * @return Response
     */
    public function deactivateAction(WorkflowDefinition $workflowDefinition)
    {
        $workflowManager = $this->get('oro_workflow.manager');

        $workflowManager->resetWorkflowData($workflowDefinition);
        $workflowManager->deactivateWorkflow($workflowDefinition);

        return $this->handleView(
            $this->view(
                array(
                    'successful' => true,
                    'message' => $this->get('translator')->trans('Workflow deactivated')
                ),
                Codes::HTTP_OK
            )
        );
    }

    /**
     * @param string $message
     * @param int $code
     * @return Response
     */
    protected function handleError($message, $code)
    {
        return $this->handleView(
            $this->view(
                $this->formatErrorResponse($message),
                $code
            )
        );
    }

    /**
     * @param string $message
     * @return array
     */
    protected function formatErrorResponse($message)
    {
        return array('message' => $message);
    }
}
