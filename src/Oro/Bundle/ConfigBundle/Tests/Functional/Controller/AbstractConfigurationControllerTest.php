<?php

namespace Oro\Bundle\ConfigBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

abstract class AbstractConfigurationControllerTest extends WebTestCase
{
    /**
     * @param array $parameters
     * @return string
     */
    abstract protected function getRequestUrl(array $parameters);

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    /**
     * @param array $parameters
     * @param array $expected
     *
     * @dataProvider getParameters
     */
    public function testSystemActionDesktopVersion($parameters, array $expected)
    {
        $crawler = $this->client->request('GET', $this->getRequestUrl($parameters));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertNotContains(
            'System configuration is not available in mobile version. Please open the page on the desktop.',
            $crawler->html()
        );

        foreach ($expected as $value) {
            $this->assertContains($value, $crawler->html());
        }
    }

    /**
     * @param array $parameters
     *
     * @dataProvider getParameters
     */
    public function testSystemActionMobileVersion($parameters)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getRequestUrl($parameters),
            [],
            [],
            [
                'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 10_0 like Mac OS X) '.
                    'AppleWebKit/602.1.38 (KHTML, like Gecko) Version/10.0 Mobile/14A300 Safari/602.1'
            ]
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains(
            'System configuration is not available in mobile version. Please open the page on the desktop.',
            $crawler->html()
        );
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return [
            'main user configuration page' => [
                'parameters' => [
                    'activeGroup' => null,
                    'activeSubGroup' => null,
                ],
                'expected' => ['Application Settings', 'Application URL']
            ],
            'user configuration sub page' => [
                'parameters' => [
                    'activeGroup' => 'platform',
                    'activeSubGroup' => 'localization',
                ],
                'expected' => ['Location options', 'Primary Location']
            ],
        ];
    }
}
