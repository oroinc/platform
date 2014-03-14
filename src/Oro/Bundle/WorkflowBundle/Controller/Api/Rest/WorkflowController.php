<?php

namespace Oro\Bundle\WorkflowBundle\Controller\Api\Rest;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\Rest\Util\Codes;

// Annotations import
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;

// Exceptions
use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException;
use Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException;
use Oro\Bundle\WorkflowBundle\Exception\ForbiddenTransitionException;
use Oro\Bundle\WorkflowBundle\Exception\UnknownAttributeException;

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
     * @return Response
     */
    public function startAction($workflowName, $transitionName)
    {
        try {
            /** @var WorkflowManager $workflowManager */
            $workflowManager = $this->get('oro_workflow.manager');

            $entityId = $this->getRequest()->get('entityId', 0);
            $data = $this->getRequest()->get('data');
            $dataArray = array();
            if ($data) {
                $serializer = $this->get('oro_workflow.serializer.data.serializer');
                $serializer->setWorkflowName($workflowName);
                /** @var WorkflowData $data */
                $data = $serializer->deserialize(
                    $data,
                    'Oro\Bundle\WorkflowBundle\Model\WorkflowData',
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
        $em = $this->getDoctrine()->getManager();
        $em->remove($workflowItem);
        $em->flush();
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
        $workflowName = $workflowDefinition->getName();
        $entityConfig = $this->getEntityConfig($workflowDefinition->getRelatedEntity());
        $entityConfig->set('active_workflow', $workflowName);
        $this->persistEntityConfig($entityConfig);

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
     *      "/api/rest/{version}/workflow/deactivate/{entityClass}",
     *      requirements={"version"="latest|v1"},
     *      defaults={"version"="latest", "_format"="json"}
     * )
     * @ApiDoc(description="Deactivate workflow", resource=true)
     * @AclAncestor("oro_workflow")
     *
     * @param string $entityClass
     * @return Response
     */
    public function deactivateAction($entityClass)
    {
        $entityConfig = $this->getEntityConfig($entityClass);
        $entityConfig->set('active_workflow', null);
        $this->persistEntityConfig($entityConfig);

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

    /**
     * @param string $entityClass
     * @return ConfigInterface
     * @throws WorkflowException
     */
    protected function getEntityConfig($entityClass)
    {
        /** @var ConfigProviderInterface $workflowConfigProvider */
        $workflowConfigProvider = $this->get('oro_entity_config.provider.workflow');
        if (!$workflowConfigProvider->hasConfig($entityClass)) {
            throw new WorkflowException(sprintf('Entity %s is not configurable', $entityClass));
        }

        return $workflowConfigProvider->getConfig($entityClass);
    }

    /**
     * @param ConfigInterface $entityConfig
     */
    protected function persistEntityConfig(ConfigInterface $entityConfig)
    {
        /** @var ConfigManager $configManager */
        $configManager = $this->get('oro_entity_config.config_manager');
        $configManager->persist($entityConfig);
        $configManager->flush();
    }
}
