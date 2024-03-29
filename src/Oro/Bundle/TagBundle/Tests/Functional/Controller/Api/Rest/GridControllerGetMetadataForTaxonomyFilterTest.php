<?php

namespace Oro\Bundle\TagBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TagBundle\Tests\Functional\DataFixtures\LoadTaxonomyWithTagsData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;

class GridControllerGetMetadataForTaxonomyFilterTest extends WebTestCase
{
    private const GRID_NAME = 'tag-grid';
    private const FILTER_NAME = 'taxonomyName';

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->loadFixtures([
            LoadOrganization::class,
            LoadUser::class,
            LoadTaxonomyWithTagsData::class
        ]);
    }

    public function testGetMetadata(): void
    {
        $url = $this->getUrl(
            'oro_datagrid_filter_metadata',
            ['gridName' => self::GRID_NAME, 'filterNames[]' => self::FILTER_NAME]
        );
        $this->client->jsonRequest(
            'GET',
            $url
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $choices = array_column($result[self::FILTER_NAME]['choices'], 'label');

        $this->assertNotEmpty($result);
        $this->assertCount(3, $choices);
        $this->assertEquals([
            LoadTaxonomyWithTagsData::THIRD_TAXONOMY,
            LoadTaxonomyWithTagsData::SECOND_TAXONOMY,
            LoadTaxonomyWithTagsData::FIRST_TAXONOMY
        ], $choices);
    }
}
