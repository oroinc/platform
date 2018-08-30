<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Entity\RegionTranslation;
use Oro\Bundle\AddressBundle\Entity\Repository\RegionTranslationRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RegionTranslationRepositoryTest extends WebTestCase
{
    /** @var RegionTranslationRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->initClient();

        $this->repository = $this->getContainer()->get('doctrine')->getRepository(RegionTranslation::class);
    }

    public function testUpdateTranslations()
    {
        $this->repository->updateTranslations(
            [
                'US-FL' => 'Floride',
                'DE-HH' => 'Hambourg',
            ],
            'fr'
        );

        $actual = $this->repository->findBy(['locale' => 'fr']);

        $this->assertCount(2, $actual);
        $this->assertTranslationExists('US-FL', 'Floride', $actual);
        $this->assertTranslationExists('DE-HH', 'Hambourg', $actual);
    }

    /**
     * @param string $expectedCode
     * @param string $expectedTranslation
     * @param array|RegionTranslation[] $translations
     */
    private function assertTranslationExists(string $expectedCode, string $expectedTranslation, array $translations)
    {
        $actual = null;
        foreach ($translations as $translation) {
            if ($translation->getForeignKey() === $expectedCode) {
                $actual = $translation;
                break;
            }
        }

        $this->assertNotNull($actual);
        $this->assertEquals('name', $actual->getField());
        $this->assertEquals(Region::class, $actual->getObjectClass());
        $this->assertEquals($expectedTranslation, $actual->getContent());
    }
}
