<?php

namespace Oro\Bundle\DataGridBundle\Tests\Functional\Extension\NoDataMessages;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslations;

class NoDataMessagesExtensionTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadTranslations::class
        ]);
    }

    public function testIndexPage()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_test_item_index'));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $gridAttr = $crawler->filter('[id^=grid-items-grid]')->first()->attr('data-page-component-options');

        $gridJsonElements = json_decode(html_entity_decode($gridAttr), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(
            'oro.testframework.item.entity_plural_label',
            $gridJsonElements['metadata']['options']['entityHint']
        );

        $this->assertEquals(
            [
                'emptyGrid' => 'translation.trans1',
                'emptyFilteredGrid' => 'translation.trans2'
            ],
            $gridJsonElements['metadata']['options']['noDataMessages']
        );
    }
}
