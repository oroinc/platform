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

class EntityNameProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var NameFormatter|\PHPUnit\Framework\MockObject\MockObject */
    protected $nameFormatter;

    /** @var DQLNameFormatter|\PHPUnit\Framework\MockObject\MockObject */
    protected $dqlNameFormatter;

    /** @var EntityNameProvider */
    protected $provider;

    protected function setUp()
    {
        $this->nameFormatter = $this->createMock(NameFormatter::class);

        /** @var ServiceLink|\PHPUnit\Framework\MockObject\MockObject $nameFormatterLink */
        $nameFormatterLink = $this->createMock(ServiceLink::class);
        $nameFormatterLink->expects($this->any())
            ->method('getService')
            ->willReturn($this->nameFormatter);

        $this->dqlNameFormatter = $this->createMock(DQLNameFormatter::class);

        /** @var ServiceLink|\PHPUnit\Framework\MockObject\MockObject $dqlNameFormatterLink */
        $dqlNameFormatterLink = $this->createMock(ServiceLink::class);
        $dqlNameFormatterLink->expects($this->any())
            ->method('getService')
            ->willReturn($this->dqlNameFormatter);

        $this->provider = new EntityNameProvider($nameFormatterLink, $dqlNameFormatterLink);
    }

    public function testGetNameForShortFormat()
    {
        $this->assertFalse($this->provider->getName(EntityNameProviderInterface::SHORT, 'en', new User()));
    }

    public function testGetNameForLocale()
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

    public function testGetNameForLocalization()
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

    public function testGetNameDQLForShortFormat()
    {
        $this->assertFalse($this->provider->getNameDQL(EntityNameProviderInterface::SHORT, 'en', User::class, 'user'));
    }

    public function testGetNameDQLForLocale()
    {
        $entity = new User();
        $locale = 'en';
        $alias = 'user';

        $this->dqlNameFormatter->expects($this->once())
            ->method('getFormattedNameDQL')
            ->with($alias, $entity, $locale)
            ->willReturn('formatted value');

        $this->assertEquals(
            'formatted value',
            $this->provider->getNameDQL(EntityNameProviderInterface::FULL, $locale, $entity, $alias)
        );
    }

    public function testGetNameDQLForLocalization()
    {
        $entity = new User();
        $locale = $this->getLocalization('en');
        $alias = 'user';

        $this->dqlNameFormatter->expects($this->once())
            ->method('getFormattedNameDQL')
            ->with($alias, $entity, 'en')
            ->willReturn('formatted value');

        $this->assertEquals(
            'formatted value',
            $this->provider->getNameDQL(EntityNameProviderInterface::FULL, $locale, $entity, $alias)
        );
    }

    /**
     * @param string $code
     * @return Localization
     */
    protected function getLocalization($code)
    {
        $language = new Language();
        $language->setCode($code);

        $localization = new Localization();
        $localization->setLanguage($language);

        return $localization;
    }
}
