<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional;

use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Bundle\ActionBundle\Provider\ButtonProvider;
use Oro\Bundle\ActionBundle\Provider\ButtonSearchContextProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

abstract class ActionTestCase extends WebTestCase
{
    use OperationAwareTestTrait;

    /** @var ActionGroupRegistry */
    private $actionGroupRegistry;

    /**
     * @return ActionGroupRegistry
     */
    protected function getActionGroupRegistry()
    {
        if (null === $this->actionGroupRegistry) {
            $this->actionGroupRegistry = $this->getContainer()->get(ActionGroupRegistry::class);
        }

        return $this->actionGroupRegistry;
    }

    /**
     * @param string $name
     * @param array $data
     * @return ActionData
     */
    protected function executeActionGroup($name, array $data = [])
    {
        $actionGroup = $this->getActionGroupRegistry()->get($name);

        return $actionGroup->execute(new ActionData($data));
    }

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
     * @param string $buttonName
     * @param mixed $entityId
     * @param string $entityClass
     * @param array $data
     */
    protected function assertActionButton($buttonName, $entityId, $entityClass, array $data = [])
    {
        $request = array_merge([
            ContextHelper::ENTITY_CLASS_PARAM => $entityClass,
            ContextHelper::ENTITY_ID_PARAM => $entityId,
        ], $data);

        /* @var RequestStack $requestStack */
        $requestStack = $this->getContainer()->get('request_stack');
        $requestStack->push(new Request($request));

        /* @var ButtonProvider $provider */
        $provider = $this->getContainer()->get('oro_action.provider.button');

        /* @var ButtonSearchContextProvider $contextProvider */
        $contextProvider = $this->getContainer()->get('oro_action.provider.button_search_context');
        $context = $contextProvider->getButtonSearchContext();

        $buttons = array_map(function (ButtonInterface $button) {
            return $button->getName();
        }, $provider->findAvailable($context));

        $this->assertContains($buttonName, $buttons);
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
        $this->assertExecuteOperation('DELETE', $entityId, $entityClass, [], $server, $expectedCode);

        $this->assertEquals(
            [
                'success' => $isSuccess,
                'message' => '',
                'messages' => [],
                'redirectUrl' => $this->getUrl($redirectUrl),
                'pageReload' => true
            ],
            json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)
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
        self::assertStringContainsString($message, $crawler->html());
    }
}
