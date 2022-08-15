<?php

namespace Oro\Bundle\LocaleBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizedFallbackValueRepository;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class LocalizedFallbackValueRepositoryTest extends WebTestCase
{
    private EntityManager $entityManager;
    private LocalizedFallbackValueRepository $repository;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadLocalizationData::class]);
        $this->entityManager = self::getContainer()->get('doctrine')
            ->getManagerForClass(LocalizedFallbackValue::class);
        $this->repository = $this->entityManager->getRepository(LocalizedFallbackValue::class);
    }

    public function testGetParentIdByFallbackValue(): void
    {
        /** @var Localization $localization */
        $localization = $this->entityManager->getRepository(Localization::class)
            ->findOneBy(['formattingCode' => 'en_CA']);
        $defaultValue = $localization->getTitles()->first();

        $parentId = $this->repository->getParentIdByFallbackValue(Localization::class, 'titles', $defaultValue);

        $this->assertEquals($localization->getId(), $parentId);
    }
}
