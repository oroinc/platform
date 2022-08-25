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
        self::assertStringContainsString('999999', $crawler->html());
        self::assertStringContainsString('ca205501-a584-4e16-bb19-0226cbb9e1c8', $crawler->html());
    }

    /**
     * @dataProvider idsDataProvider
     */
    public function testAuditHistory($entityId)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_dataaudit_history', ['entity' => \stdClass::class, 'id' => $entityId]),
            ['_widgetContainer' => 'dialog']
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        self::assertStringContainsString($entityId, $crawler->html());
    }

    public function idsDataProvider(): array
    {
        return [
            'integer' => [999999],
            'string' => ['ca205501-a584-4e16-bb19-0226cbb9e1c8']
        ];
    }
}
