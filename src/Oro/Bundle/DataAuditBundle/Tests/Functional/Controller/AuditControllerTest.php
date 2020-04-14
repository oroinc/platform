<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\Controller;

use Oro\Bundle\DataAuditBundle\Tests\Functional\DataFixtures\LoadAuditData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class AuditControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadAuditData::class]);
    }

    public function testAuditIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_dataaudit_index'));

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertStringContainsString('999999', $crawler->html());
        static::assertStringContainsString('ca205501-a584-4e16-bb19-0226cbb9e1c8', $crawler->html());
    }

    public function testAuditHistory()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_dataaudit_history', ['entity' => \stdClass::class, 'id' => 999999]),
            ['_widgetContainer' => 'dialog']
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertStringContainsString('999999', $crawler->html());
    }

    public function testAuditHistoryStringId()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_dataaudit_history',
                ['entity' => \stdClass::class, 'id' => 'ca205501-a584-4e16-bb19-0226cbb9e1c8']
            ),
            ['_widgetContainer' => 'dialog']
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertStringContainsString('ca205501-a584-4e16-bb19-0226cbb9e1c8', $crawler->html());
    }
}
