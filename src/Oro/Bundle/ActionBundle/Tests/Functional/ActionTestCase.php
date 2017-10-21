<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

abstract class ActionTestCase extends WebTestCase
{
    /**
     * @return string
     */
    protected function getOperationExecutionRoute()
    {
        return 'oro_action_operation_execute';
    }

    /**
     * @return string
     */
    protected function getOperationDialogRoute()
    {
        return 'oro_action_widget_form';
    }

    /**
     * @param string $operationName
     * @param mixed $entityId
     * @param string $entityClass
     * @param array $data
     * @param array $server
     * @param int $expectedCode
     *
     * @return Crawler
     */
    protected function assertExecuteOperation(
        $operationName,
        $entityId,
        $entityClass,
        array $data = [],
        array $server = ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
        $expectedCode = Response::HTTP_OK
    ) {
        $operationExecutionRoute = $this->getOperationExecutionRoute();
        $data  = array_merge(
            [
                'operationName' => $operationName,
                'entityId'      => $entityId,
                'entityClass'   => $entityClass
            ],
            $data
        );
        $url = $this->getUrl($operationExecutionRoute, $data);
        $dataGrid = $data['datagrid'] ?? null;
        $params   = $this->getOperationExecuteParams($operationName, $entityId, $entityClass, $dataGrid);
        $crawler  = $this->client->request('POST', $url, $params, [], $server);

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), $expectedCode);

        return $crawler;
    }

    /**
     * @param mixed $entityId
     * @param string $entityClass
     * @param string $redirectUrl
     * @param bool $isSuccess
     * @param array $server
     * @param int $expectedCode
     */
    protected function assertDeleteOperation(
        $entityId,
        $entityClass,
        $redirectUrl,
        $isSuccess = true,
        array $server = ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
        $expectedCode = Response::HTTP_OK
    ) {
        $container = $this->getContainer();

        if ($container->hasParameter($entityClass)) {
            $entityClass = $container->getParameter($entityClass);
        }

        $this->assertExecuteOperation('DELETE', $entityId, $entityClass, [], $server, $expectedCode);

        $this->assertEquals(
            [
                'success' => $isSuccess,
                'message' => '',
                'messages' => [],
                'redirectUrl' => $this->getUrl($redirectUrl)
            ],
            json_decode($this->client->getResponse()->getContent(), true)
        );
    }

    /**
     * @param string $operationName
     * @param mixed $entityId
     * @param string $entityClass
     * @param array $data
     * @param array $server
     * @return Crawler
     */
    protected function assertOperationForm(
        $operationName,
        $entityId,
        $entityClass,
        array $data = [],
        array $server = []
    ) {
        $url = $this->getUrl($this->getOperationDialogRoute(), array_merge([
                'operationName' => $operationName,
                'entityId' => $entityId,
                'entityClass' => $entityClass,
                '_widgetContainer' => 'dialog',
                '_wid' => 'test-uuid',
        ], $data));

        $server['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

        $crawler = $this->client->request('GET', $url, [], [], $server);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        return $crawler;
    }

    /**
     * @param Form $form
     * @param string $message
     */
    protected function assertOperationFormSubmitted(Form $form, $message)
    {
        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains($message, $crawler->html());
    }

    /**
     * @param $operationName
     * @param $entityId
     * @param $entityClass
     * @param $datagrid
     *
     * @return array
     */
    protected function getOperationExecuteParams($operationName, $entityId, $entityClass, $datagrid = null)
    {
        $actionContext = [
            'entityId'    => $entityId,
            'entityClass' => $entityClass,
            'datagrid'    => $datagrid
        ];
        $container = self::getContainer();
        $operation = $container->get('oro_action.operation_registry')->findByName($operationName);
        $actionData = $container->get('oro_action.helper.context')->getActionData($actionContext);

        $tokenData = $container
            ->get('oro_action.operation.execution.form_provider')
            ->createTokenData($operation, $actionData);
        // this is done because of unclear behaviour symfony mocked token session storage
        // which do not save data before embedded request done and created data do not available in sub request
        // in the test environment
        $container->get('session')->save();

        return $tokenData;
    }
}
