<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\LocaleBundle\Provider\EntityNameProvider;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\DependencyInjection\ServiceLink;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EntityNameProviderTest extends TestCase
{
    private NameFormatter&MockObject $nameFormatter;
    private DQLNameFormatter&MockObject $dqlNameFormatter;
    private EntityNameProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->nameFormatter = $this->createMock(NameFormatter::class);

        $nameFormatterLink = $this->createMock(ServiceLink::class);
        $nameFormatterLink->expects($this->any())
            ->method('getService')
            ->willReturn($this->nameFormatter);

        $this->dqlNameFormatter = $this->createMock(DQLNameFormatter::class);

        $dqlNameFormatterLink = $this->createMock(ServiceLink::class);
        $dqlNameFormatterLink->expects($this->any())
            ->method('getService')
            ->willReturn($this->dqlNameFormatter);

        $this->provider = new EntityNameProvider($nameFormatterLink, $dqlNameFormatterLink);
    }

    public function testGetNameForShortFormat(): void
    {
        $this->assertFalse($this->provider->getName(EntityNameProviderInterface::SHORT, 'en', new User()));
    }

    public function testGetNameForLocale(): void
    {
        $entity = new User();
        $locale = 'en';

        $this->nameFormatter->expects($this->once())
            ->method('format')
            ->with($entity, $locale)
            ->willReturn('formatted value');

        $this->assertEquals(
            'formatted value',
            $this->provider->getName(EntityNameProviderInterface::FULL, $locale, $entity)
        );
    }

    public function testGetNameForLocalization(): void
    {
        $entity = new User();
        $locale = $this->getLocalization('en');

        $this->nameFormatter->expects($this->once())
            ->method('format')
            ->with($entity, 'en')
            ->willReturn('formatted value');

        $this->assertEquals(
            'formatted value',
            $this->provider->getName(EntityNameProviderInterface::FULL, $locale, $entity)
        );
    }

    public function testGetNameDQLForShortFormat(): void
    {
        $this->assertFalse($this->provider->getNameDQL(EntityNameProviderInterface::SHORT, 'en', User::class, 'user'));
    }

    public function testGetNameDQLForLocale(): void
    {
        $entityClass = User::class;
        $locale = 'en';
        $alias = 'user';

        $this->dqlNameFormatter->expects($this->once())
            ->method('getFormattedNameDQL')
            ->with($alias, $entityClass, $locale)
            ->willReturn('formatted value');

        $this->assertEquals(
            'formatted value',
            $this->provider->getNameDQL(EntityNameProviderInterface::FULL, $locale, $entityClass, $alias)
        );
    }

    public function testGetNameDQLForLocalization(): void
    {
        $entityClass = User::class;
        $locale = $this->getLocalization('en');
        $alias = 'user';

        $this->dqlNameFormatter->expects($this->once())
            ->method('getFormattedNameDQL')
            ->with($alias, $entityClass, 'en')
            ->willReturn('formatted value');

        $this->assertEquals(
            'formatted value',
            $this->provider->getNameDQL(EntityNameProviderInterface::FULL, $locale, $entityClass, $alias)
        );
    }

    private function getLocalization(string $code): Localization
    {
        $language = new Language();
        $language->setCode($code);

        $localization = new Localization();
        $localization->setLanguage($language);

        return $localization;
    }
}
