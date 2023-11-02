<?php

namespace Oro\Bundle\ConfigBundle\Tests\Functional\Api\RestPlain;

use Oro\Bundle\ApiBundle\Tests\Functional\RestPlainApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class ConfigurationTest extends RestPlainApiTestCase
{
    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'configuration']);

        // check that the result is a list of configuration section ids
        // check that each returned section is accessible
        $content = self::jsonToArray($response->getContent());
        foreach ($content as $key => $sectionId) {
            self::assertIsString(
                $sectionId,
                sprintf(
                    'get_list. item index: %s. expected a string, got "%s"',
                    $key,
                    get_debug_type($sectionId)
                )
            );
            $this->checkGet($sectionId);
        }
    }

    private function checkGet(string $sectionId): void
    {
        $response = $this->get(['entity' => 'configuration', 'id' => $sectionId]);

        $content = self::jsonToArray($response->getContent());
        // check that the result is a list of configuration options
        foreach ($content as $key => $item) {
            self::assertArrayHasKey('key', $item, sprintf('get->%s. item index: %s', $sectionId, $key));
        }
    }

    public function testTryToGetListWithSorting(): void
    {
        $response = $this->cget(
            ['entity' => 'configuration'],
            ['sort' => 'id'],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The filter is not supported.',
                'source' => 'sort'
            ],
            $response
        );
    }

    public function testTryToGetUnknown(): void
    {
        $response = $this->get(
            ['entity' => 'configuration', 'id' => 'unknown.section'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testGetListWithScope(): void
    {
        $response = $this->cget(['entity' => 'configuration'], ['scope' => 'global']);
        self::assertNotEmpty(self::jsonToArray($response->getContent()));
    }

    public function testGetWithScope(): void
    {
        $response = $this->get(['entity' => 'configuration', 'id' => 'application'], ['scope' => 'global']);
        self::assertNotEmpty(self::jsonToArray($response->getContent()));
    }
}
