<?php

namespace Oro\Bundle\WorkflowBundle\Controller\Api\Rest;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\ForbiddenTransitionException;
use Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException;
use Oro\Bundle\WorkflowBundle\Exception\UnknownAttributeException;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * REST API controller for workflows.
 */
class WorkflowController extends AbstractFOSRestController
{
    /**
     * Returns:
     * - HTTP_OK (200) response: array('workflowItem' => array('id' => int, 'result' => array(...), ...))
     * - HTTP_BAD_REQUEST (400) response: array('message' => errorMessageString)
     * - HTTP_FORBIDDEN (403) response: array('message' => errorMessageString)
     * - HTTP_NOT_FOUND (404) response: array('message' => errorMessageString)
     * - HTTP_INTERNAL_SERVER_ERROR (500) response: array('message' => errorMessageString)
     *
     * @ApiDoc(description="Start workflow for entity from transition", resource=true)
     *
     * @param string $workflowName
     * @param string $transitionName
     * @param Request $request
     * @return Response
     */
    public function startAction($workflowName, $transitionName, Request $request)
    {
        $errors = new ArrayCollection();

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

            $transition = $workflow->getTransitionManager()->getTransition($transitionName);
            if (!$transition->isEmptyInitOptions()) {
                $contextAttribute = $transition->getInitContextAttribute();
                $dataArray[$contextAttribute] = $this->get('oro_action.provider.button_search_context')
                    ->getButtonSearchContext();
                $entityId = null;
            }

            $entity = $this->getOrCreateEntityReference($entityClass, $entityId);
            $workflowItem = $workflowManager->startWorkflow(
                $workflowName,
                $entity,
                $transitionName,
                $dataArray,
                true,
                $errors
            );
        } catch (HttpException $e) {
            return $this->handleError($this->buildMessageString($errors, $e), $e->getStatusCode());
        } catch (WorkflowNotFoundException $e) {
            return $this->handleError($this->buildMessageString($errors, $e), Response::HTTP_NOT_FOUND);
        } catch (UnknownAttributeException $e) {
            return $this->handleError($this->buildMessageString($errors, $e), Response::HTTP_BAD_REQUEST);
        } catch (InvalidTransitionException $e) {
            return $this->handleError($this->buildMessageString($errors, $e), Response::HTTP_BAD_REQUEST);
        } catch (ForbiddenTransitionException $e) {
            return $this->handleError($this->buildMessageString($errors, $e), Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return $this->handleError($this->buildMessageString($errors, $e), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->handleView(
            $this->view(['workflowItem' => $this->serializeWorkflowItem($workflowItem)], Response::HTTP_OK)
        );
    }

    private function buildMessageString(Collection $errors, \Exception $e): string
    {
        $translator = $this->get('translator');

        $messages = $errors->map(
            static function ($error) use ($translator) {
                if (!isset($error['message'])) {
                    return null;
                }

                return $translator->trans($error['message'], $error['parameters'] ?? []);
            }
        );

        return implode(' ', array_filter($messages->toArray())) ?: $e->getMessage();
    }

    /**
     * Try to get or create reference to entity
     *
     * @param string $entityClass
     * @param mixed $entityId
     * @throws BadRequestHttpException
     * @return mixed
     */
    protected function getOrCreateEntityReference($entityClass, $entityId)
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
     * @ParamConverter("workflowItem", options={"id"="workflowItemId"})
     * @ApiDoc(description="Perform transition for workflow item", resource=true)
     *
     * @param WorkflowItem $workflowItem
     * @param string $transitionName
     * @return Response
     */
    public function transitAction(WorkflowItem $workflowItem, $transitionName)
    {
        $errors = new ArrayCollection();

        try {
            $this->get('oro_workflow.manager')->transit($workflowItem, $transitionName, $errors);
        } catch (WorkflowNotFoundException $e) {
            return $this->handleError($this->buildMessageString($errors, $e), Response::HTTP_NOT_FOUND);
        } catch (InvalidTransitionException $e) {
            return $this->handleError($this->buildMessageString($errors, $e), Response::HTTP_BAD_REQUEST);
        } catch (ForbiddenTransitionException $e) {
            return $this->handleError($this->buildMessageString($errors, $e), Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return $this->handleError($this->buildMessageString($errors, $e), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $data = ['workflowItem' => $this->serializeWorkflowItem($workflowItem)];
        $redirectUrl = $workflowItem->getResult()->redirectUrl;
        if ($redirectUrl) {
            $data['redirectUrl'] = $redirectUrl;
        }

        return $this->handleView($this->view($data, Response::HTTP_OK));
    }

    /**
     * Returns
     * - HTTP_OK (200) response: array('workflowItem' => array('id' => int, 'result' => array(...), ...))
     *
     * @ParamConverter("workflowItem", options={"id"="workflowItemId"})
     * @ApiDoc(description="Get workflow item", resource=true)
     *
     * @param WorkflowItem $workflowItem
     * @return Response
     */
    public function getAction(WorkflowItem $workflowItem)
    {
        return $this->handleView(
            $this->view(['workflowItem' => $this->serializeWorkflowItem($workflowItem)], Response::HTTP_OK)
        );
    }

    /**
     * Delete workflow item
     *
     * Returns
     * - HTTP_NO_CONTENT (204)
     *
     * @ParamConverter("workflowItem", options={"id"="workflowItemId"})
     * @ApiDoc(description="Delete workflow item", resource=true)
     *
     * @param WorkflowItem $workflowItem
     * @return Response
     */
    public function deleteAction(WorkflowItem $workflowItem)
    {
        $this->get('oro_workflow.manager')->resetWorkflowItem($workflowItem);

        return $this->handleView($this->view(null, Response::HTTP_NO_CONTENT));
    }

    /**
     * Activate workflow
     *
     * Returns
     * - HTTP_OK (200)
     *
     * @ApiDoc(description="Activate workflow", resource=true)
     *
     * @param WorkflowDefinition $workflowDefinition
     * @return Response
     */
    public function activateAction(WorkflowDefinition $workflowDefinition)
    {
        $workflowManager = $this->get('oro_workflow.registry.workflow_manager')->getManager();

        $workflowManager->resetWorkflowData($workflowDefinition->getName());
        $workflowManager->activateWorkflow($workflowDefinition->getName());

        return $this->handleView(
            $this->view(
                [
                    'successful' => true,
                    'message' => $this->get('translator')->trans('Workflow activated')
                ],
                Response::HTTP_OK
            )
        );
    }

    /**
     * Deactivate workflow
     *
     * Returns
     * - HTTP_OK (204)
     *
     * @ApiDoc(description="Deactivate workflow", resource=true)
     *
     * @param WorkflowDefinition $workflowDefinition
     * @return Response
     */
    public function deactivateAction(WorkflowDefinition $workflowDefinition)
    {
        $workflowManager = $this->get('oro_workflow.registry.workflow_manager')->getManager();

        $workflowManager->resetWorkflowData($workflowDefinition->getName());
        $workflowManager->deactivateWorkflow($workflowDefinition->getName());

        return $this->handleView(
            $this->view(
                [
                    'successful' => true,
                    'message' => $this->get('translator')->trans('Workflow deactivated')
                ],
                Response::HTTP_OK
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
        return $this->handleView($this->view(['message' => $message], $code));
    }

    /**
     * {@inheritDoc}
     */
    protected function handleView(View $view)
    {
        $view->getContext()->setSerializeNull(true);

        return parent::handleView($view);
    }

    protected function serializeWorkflowItem(?WorkflowItem $workflowItem): ?array
    {
        if (null === $workflowItem) {
            return null;
        }

        return $this->get('oro_workflow.workflow_item_serializer')->serialize($workflowItem);
    }
}
