<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationKeyRepository;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslations;

class TranslationKeyRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadTranslations::class]);
    }

    private function getRepository(): TranslationKeyRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(TranslationKey::class);
    }

    public function testGetCount(): void
    {
        $this->assertGreaterThanOrEqual(3, $this->getRepository()->getCount());
    }

    public function testFindAvailableDomains(): void
    {
        $domains = $this->getRepository()->findAvailableDomains();

        $this->assertContains(LoadTranslations::TRANSLATION_KEY_DOMAIN, $domains);
        $this->assertGreaterThanOrEqual(1, count($domains));
    }

    public function testGetTranslationKeysData(): void
    {
        $data = $this->getRepository()->getTranslationKeysData();
        $this->assertArrayHasKey(LoadTranslations::TRANSLATION_KEY_DOMAIN, $data);
        $expectedTranslationKeys = [
            LoadTranslations::TRANSLATION_KEY_1,
            LoadTranslations::TRANSLATION_KEY_2,
            LoadTranslations::TRANSLATION_KEY_3,
            LoadTranslations::TRANSLATION_KEY_4,
            LoadTranslations::TRANSLATION_KEY_5,
        ];

        $this->assertEquals($expectedTranslationKeys, array_keys($data[LoadTranslations::TRANSLATION_KEY_DOMAIN]));
    }
}
