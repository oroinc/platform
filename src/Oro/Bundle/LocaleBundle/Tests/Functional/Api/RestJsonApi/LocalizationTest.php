<?php

namespace Oro\Bundle\LocaleBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;

class LocalizationTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadLocalizationData::class]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'localizations']);
        $this->assertResponseContains('cget_localization.yml', $response);
    }

    public function testGetListFilterByPredefinedLanguageCode(): void
    {
        $response = $this->cget(['entity' => 'localizations'], ['filter[languageCode]' => 'user']);
        $this->assertResponseContains('cget_localization_filter_by_predefined_language.yml', $response);
    }

    public function testGet(): void
    {
        $response = $this->get(['entity' => 'localizations', 'id' => '<toString(@en_US->id)>']);
        $this->assertResponseContains('get_localization.yml', $response);
    }

    public function testTryToCreate(): void
    {
        $response = $this->post(
            ['entity' => 'localizations'],
            ['data' => ['type' => 'localizations']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToUpdate(): void
    {
        $response = $this->patch(
            ['entity' => 'localizations', 'id' => '1'],
            ['data' => ['type' => 'localizations', 'id' => '1']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'localizations', 'id' => '1'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'localizations'],
            ['filter' => ['id' => '1']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
