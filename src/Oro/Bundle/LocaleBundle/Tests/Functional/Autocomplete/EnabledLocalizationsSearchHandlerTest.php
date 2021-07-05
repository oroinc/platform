<?php

namespace Oro\Bundle\LocaleBundle\Tests\Functional\Autocomplete;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\LocaleBundle\Autocomplete\EnabledLocalizationsSearchHandler;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EnabledLocalizationsSearchHandlerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    /**
     * @var EnabledLocalizationsSearchHandler
     */
    private $searchHandler;

    protected function setUp(): void
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->loadFixtures([LoadLocalizationData::class]);
        $this->searchHandler = self::getContainer()
            ->get('oro_locale.autocomplete.enabled_localizations.search_handler');
        self::getContainer()->get('oro_search.search.engine.indexer')->reindex(Localization::class);
    }

    public function testSearch(): void
    {
        $result = $this->searchHandler->search('', 1, 10, false);
        $this->assertSearchResult($result, []);

        $result = $this->searchHandler->search(';', 1, 10, false);
        $this->assertSearchResult(
            $result,
            [
                $this->getReference('en_US')->getId(),
                $this->getReference('en_CA')->getId(),
                $this->getReference('es')->getId(),
            ]
        );

        // Check with scope identifier
        self::getConfigManager('global')->set(
            Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS),
            [
                $this->getReference('en_CA')->getId(),
                $this->getReference('es')->getId(),
            ],
            2
        );
        self::getConfigManager('global')->flush(2);

        $result = $this->searchHandler->search(';2', 1, 10, false);
        $this->assertSearchResult(
            $result,
            [
                $this->getReference('en_CA')->getId(),
                $this->getReference('es')->getId(),
            ]
        );
    }

    public function testSearchById(): void
    {
        self::getConfigManager('global')->flush(2);

        $idForSearch = $this->getReference('en_CA')->getId();

        $this->assertSearchResult($this->searchHandler->search("$idForSearch;2", 1, 10, true), [$idForSearch]);
    }

    public function testSearchByIdNotExistingId(): void
    {
        self::getConfigManager('global')->flush(2);

        $this->assertSearchResult($this->searchHandler->search('77777;2', 1, 10, true), []);
    }

    public function testSearchByIdWrongScope(): void
    {
        $this->assertSearchResult($this->searchHandler->search('1;777', 1, 10, true), []);
    }

    /**
     * @param Localization[] $result
     * @param array $expected
     */
    private function assertSearchResult(array $result, array $expected): void
    {
        $searchItems = $result['results'];
        $this->assertCount(count($expected), $searchItems);
        $result = [];
        array_map(function (array $searchResult) use (&$result) {
            $result[] = $searchResult['id'];
        }, $searchItems);

        $this->assertEquals(sort($expected), sort($result));
    }
}
