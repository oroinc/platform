<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional;

/**
 * Intended for use within {@see \Oro\Bundle\TestFrameworkBundle\Test\WebTestCase}.
 * Provides common methods needed for testing operations.
 */
trait OperationAwareTestTrait
{
    protected function getOperationExecuteParams(
        string $operationName,
        string|int|array|null $entityId,
        ?string $entityClass,
        ?string $datagrid = null
    ): array {
        $actionContext = [
            'entityId' => $entityId,
            'entityClass' => $entityClass,
            'datagrid' => $datagrid,
        ];
        $container = self::getContainer();
        $operation = $container->get('oro_action.operation_registry')->findByName($operationName);
        $actionData = $container->get('oro_action.helper.context')->getActionData($actionContext);

        $this->ensureSessionIsAvailable();

        $tokenData = $container
            ->get('oro_action.operation.execution.form_provider')
            ->createTokenData($operation, $actionData);
        // This is done because of unclear behaviour symfony mocked token session storage
        // which do not save data before embedded request done and created data do not available in sub request
        // in the test environment
        $container->get('request_stack')->getSession()->save();

        return $tokenData;
    }
}
