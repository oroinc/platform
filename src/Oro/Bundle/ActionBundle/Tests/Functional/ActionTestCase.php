<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

use Oro\Component\Testing\WebTestCase;

abstract class ActionTestCase extends WebTestCase
{
    /**
     * @return string
     */
    protected function getActionExecutionRoute()
    {
        return 'oro_api_action_execute_actions';
    }

    /**
     * @return string
     */
    protected function getActionDialogRoute()
    {
        return 'oro_action_widget_form';
    }

    /**
     * @param string $actionName
     * @param mixed $entityId
     * @param string $entityClass
     * @param array $data
     * @param array $server
     * @return Crawler
     */
    protected function assertExecuteAction($actionName, $entityId, $entityClass, array $data = [], array $server = [])
    {
        $url = $this->getUrl(
            $this->getActionExecutionRoute(),
            array_merge([
                'actionName' => $actionName,
                'entityId' => $entityId,
                'entityClass' => $entityClass,
            ], $data)
        );

        $crawler = $this->client->request('GET', $url, [], [], $server);

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);

        return $crawler;
    }

    /**
     * @param string $actionName
     * @param mixed $entityId
     * @param string $entityClass
     * @param array $data
     * @param array $server
     * @return Crawler
     */
    protected function assertActionForm($actionName, $entityId, $entityClass, array $data = [], array $server = [])
    {
        $url = $this->getUrl($this->getActionDialogRoute(), array_merge([
                'actionName' => $actionName,
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
    protected function assertActionFormSubmitted(Form $form, $message)
    {
        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains($message, $crawler->html());
    }
}
