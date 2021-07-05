<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

abstract class AbstractLocalizedType extends FormIntegrationTestCase
{
    protected const LOCALIZATION_CLASS = Localization::class;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $registry;

    protected function setRegistryExpectations(): void
    {
        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($this->getLocalizations());

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('leftJoin')
            ->with('l.parentLocalization', 'parent')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('addOrderBy')
            ->with('l.id', 'ASC')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('l')
            ->willReturn($queryBuilder);

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(self::LOCALIZATION_CLASS)
            ->willReturn($repository);
    }

    /**
     * @return Localization[]
     */
    protected function getLocalizations(): array
    {
        $en   = $this->createLocalization(1, 'en', 'en');
        $enUs = $this->createLocalization(2, 'en', 'en_US', $en);
        $enCa = $this->createLocalization(3, 'en', 'en_CA', $en);

        return [$en, $enUs, $enCa];
    }

    protected function createLocalization(
        int $id,
        string $languageCode,
        string $formattingCode,
        Localization $parentLocalization = null
    ): Localization {
        $website = $this->createMock(Localization::class);
        $website->expects($this->any())
            ->method('getId')
            ->willReturn($id);
        $website->expects($this->any())
            ->method('getLanguageCode')
            ->willReturn($languageCode);
        $website->expects($this->any())
            ->method('getFormattingCode')
            ->willReturn($formattingCode);
        $website->expects($this->any())
            ->method('getParentLocalization')
            ->willReturn($parentLocalization);

        return $website;
    }
}
