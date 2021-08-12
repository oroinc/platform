<?php

namespace Oro\Bundle\WorkflowBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionHandleBuilder;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Handler\WorkflowDefinitionHandler;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * REST API controller for workflow definitions.
 */
class WorkflowDefinitionController extends AbstractFOSRestController
{
    /**
     * REST GET item
     *
     * @param WorkflowDefinition $workflowDefinition
     *
     * @ApiDoc(
     *      description="Get workflow definition",
     *      resource=true
     * )
     * @AclAncestor("oro_workflow_definition_view")
     * @return Response
     */
    public function getAction(WorkflowDefinition $workflowDefinition)
    {
        $config = $this->getWorkflowDefinitionSerializationConfig();
        $entitySerializer = $this->get('oro_workflow.rest_api.entity_serializer');
        $data = $entitySerializer->serializeEntities([$workflowDefinition], WorkflowDefinition::class, $config);

        return $this->handleView(
            $this->view($this->updateWorkflowDefinitionSerializedData($data[0], ''), Response::HTTP_OK)
        );
    }

    /**
     * Update workflow definition
     *
     * Returns
     * - HTTP_OK (200)
     * - HTTP_BAD_REQUEST (400)
     *
     * @param WorkflowDefinition $workflowDefinition
     * @param Request $request
     * @return Response
     * @ApiDoc(
     *      description="Update workflow definition",
     *      resource=true
     * )
     * @AclAncestor("oro_workflow_definition_update")
     */
    public function putAction(WorkflowDefinition $workflowDefinition, Request $request)
    {
        try {
            $configuration = $this->getConfiguration($request);
            if (!$this->isConfigurationValid($configuration)) {
                throw new \InvalidArgumentException(
                    $this->getTranslator()->trans('oro.workflow.notification.workflow.could_not_be_saved')
                );
            }

            /** @var WorkflowDefinitionHandleBuilder $definitionBuilder */
            $definitionBuilder = $this->get('oro_workflow.configuration.builder.workflow_definition.handle');
            $builtDefinition = $definitionBuilder->buildFromRawConfiguration($configuration);

            $this->getHandler()->updateWorkflowDefinition($workflowDefinition, $builtDefinition);
        } catch (\Exception $exception) {
            return $this->handleView(
                $this->view(
                    ['error' => $exception->getMessage()],
                    Response::HTTP_BAD_REQUEST
                )
            );
        }

        return $this->handleView($this->view($workflowDefinition->getName(), Response::HTTP_OK));
    }

    /**
     * Create new workflow definition
     *
     * @param Request $request
     * @param WorkflowDefinition $workflowDefinition
     * @return Response
     * @ApiDoc(
     *      description="Create new workflow definition",
     *      resource=true
     * )
     * @AclAncestor("oro_workflow_definition_create")
     */
    public function postAction(Request $request, WorkflowDefinition $workflowDefinition = null)
    {
        try {
            $configuration = $this->getConfiguration($request);
            if (!$this->isConfigurationValid($configuration)) {
                throw new \InvalidArgumentException(
                    $this->getTranslator()->trans('oro.workflow.notification.workflow.could_not_be_saved')
                );
            }

            /** @var WorkflowDefinitionHandleBuilder $definitionBuilder */
            $definitionBuilder = $this->get('oro_workflow.configuration.builder.workflow_definition.handle');
            $builtDefinition = $definitionBuilder->buildFromRawConfiguration($configuration);

            if (!$workflowDefinition) {
                $this->getHandler()->createWorkflowDefinition($builtDefinition);
            } else {
                $this->getHandler()->updateWorkflowDefinition($workflowDefinition, $builtDefinition);
            }
        } catch (\Exception $exception) {
            return $this->handleView(
                $this->view(
                    ['error' => $exception->getMessage()],
                    Response::HTTP_BAD_REQUEST
                )
            );
        }

        return $this->handleView($this->view($builtDefinition->getName(), Response::HTTP_OK));
    }

    /**
     * Delete workflow definition
     *
     * Returns
     * - HTTP_NO_CONTENT (204)
     * - HTTP_FORBIDDEN (403)
     *
     * @ApiDoc(description="Delete workflow definition")
     * @Acl(
     *      id="oro_workflow_definition_delete",
     *      type="entity",
     *      class="OroWorkflowBundle:WorkflowDefinition",
     *      permission="DELETE"
     * )
     *
     * @param WorkflowDefinition $workflowDefinition
     * @return Response
     */
    public function deleteAction(WorkflowDefinition $workflowDefinition)
    {
        if ($workflowDefinition->isSystem()) {
            return $this->handleView($this->view(null, Response::HTTP_FORBIDDEN));
        } else {
            $this->getHandler()->deleteWorkflowDefinition($workflowDefinition);

            return $this->handleView($this->view(null, Response::HTTP_NO_CONTENT));
        }
    }

    protected function getWorkflowDefinitionSerializationConfig(): array
    {
        return [
            'fields' => [
                'entityAcls' => null,
                'scopes' => null,
                'steps' => null,
                'restrictions' => ['exclude' => true]
            ]
        ];
    }

    /**
     * Converts field names to snake case and remove items with NULL value.
     */
    protected function updateWorkflowDefinitionSerializedData(array $data, string $path): array
    {
        $result = [];
        $pathPrefix = $path;
        if ('' !== $path) {
            $pathPrefix .= '.';
        }
        foreach ($data as $name => $value) {
            if (null === $value) {
                continue;
            }
            if (\is_array($value)) {
                if (ArrayUtil::isAssoc($value)) {
                    $value = $this->updateWorkflowDefinitionSerializedData($value, $pathPrefix . $name);
                } else {
                    $rows = [];
                    foreach ($value as $k => $v) {
                        if (\is_array($v)) {
                            $v = $this->updateWorkflowDefinitionSerializedData($v, $pathPrefix . $name);
                        }
                        $rows[$k] = $v;
                    }
                    $value = $rows;
                }
            }
            if ('configuration' !== $path) {
                $name = strtolower(preg_replace('/[A-Z]+/', '_\\0', $name));
            }
            $result[$name] = $value;
        }

        return $result;
    }

    /**
     * @param Request $request
     * @return array
     */
    protected function getConfiguration(Request $request)
    {
        return $request->request->all();
    }

    /**
     * @return WorkflowDefinitionHandler
     */
    protected function getHandler()
    {
        return $this->get('oro_workflow.handler.workflow_definition');
    }

    /**
     * @param array $configuration
     * @return bool
     */
    protected function isConfigurationValid(array $configuration)
    {
        $checker = $this->get('oro_workflow.configuration.checker');

        return $checker->isClean($configuration);
    }

    /**
     * @return TranslatorInterface
     */
    protected function getTranslator()
    {
        return $this->get('translator');
    }
}
