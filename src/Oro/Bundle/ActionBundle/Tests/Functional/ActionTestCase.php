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
        $url = $this->getUrl(
            $this->getOperationExecutionRoute(),
            array_merge([
                'operationName' => $operationName,
                'entityId' => $entityId,
                'entityClass' => $entityClass,
            ], $data)
        );

        $crawler = $this->client->request('GET', $url, [], [], $server);

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
}
