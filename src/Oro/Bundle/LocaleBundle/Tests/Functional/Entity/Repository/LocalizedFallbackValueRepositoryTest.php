<?php

namespace Oro\Bundle\LocaleBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizedFallbackValueRepository;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Unit\EntityTrait;

class LocalizedFallbackValueRepositoryTest extends WebTestCase
{
    use EntityTrait;

    protected EntityManager $entityManager;

    protected LocalizedFallbackValueRepository $repository;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadLocalizationData::class]);
        $this->entityManager = self::getContainer()
            ->get('doctrine')
            ->getManagerForClass(LocalizedFallbackValue::class);
        $this->repository = $this->entityManager->getRepository('OroLocaleBundle:LocalizedFallbackValue');
    }

    public function testGetParentIdByFallbackValue(): void
    {
        $localRepo = $this->entityManager->getRepository(Localization::class);
        /** @var Localization $localization */
        $localization = $localRepo->findOneByFormattingCode('en_CA');
        $defaultValue = $localization->getTitles()->first();

        $parentId = $this->repository->getParentIdByFallbackValue(Localization::class, 'titles', $defaultValue);

        $this->assertEquals($localization->getId(), $parentId);
    }
}
