<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\CountryTranslation;
use Oro\Bundle\AddressBundle\Entity\Repository\CountryTranslationRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CountryTranslationRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
    }

    private function getRepository(): CountryTranslationRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(CountryTranslation::class);
    }

    public function testUpdateTranslations()
    {
        $this->getRepository()->updateTranslations(
            [
                'US' => 'États Unis',
                'DE' => 'Allemagne',
            ],
            'fr'
        );

        $actual = $this->getRepository()->findBy(['locale' => 'fr']);

        $this->assertCount(2, $actual);
        $this->assertTranslationExists('US', 'États Unis', $actual);
        $this->assertTranslationExists('DE', 'Allemagne', $actual);
    }

    private function assertTranslationExists(
        string $expectedCode,
        string $expectedTranslation,
        array $translations
    ): void {
        $actual = null;
        /** @var CountryTranslation $translation */
        foreach ($translations as $translation) {
            if ($translation->getForeignKey() === $expectedCode) {
                $actual = $translation;
                break;
            }
        }

        $this->assertNotNull($actual);
        $this->assertEquals('name', $actual->getField());
        $this->assertEquals(Country::class, $actual->getObjectClass());
        $this->assertEquals($expectedTranslation, $actual->getContent());
    }
}
