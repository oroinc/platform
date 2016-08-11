<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Provider;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Extension\CurrentLocalizationExtensionInterface;
use Oro\Bundle\LocaleBundle\Provider\CurrentLocalizationProvider;

class CurrentLocalizationProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var CurrentLocalizationProvider */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->provider = new CurrentLocalizationProvider();
    }

    public function testGetCurrentLocalizationAndNoExtensions()
    {
        $this->assertNull($this->provider->getCurrentLocalization());
    }

    public function testGetCurrentLocalization()
    {
        $localization = new Localization();

        $extension1 = $this->getMock(CurrentLocalizationExtensionInterface::class);
        $extension2 = $this->getMock(CurrentLocalizationExtensionInterface::class);
        $extension3 = $this->getMock(CurrentLocalizationExtensionInterface::class);

        $extension1->expects($this->once())->method('getCurrentLocalization')->willReturn(null);
        $extension2->expects($this->once())->method('getCurrentLocalization')->willReturn($localization);
        $extension3->expects($this->never())->method('getCurrentLocalization');

        $this->provider->addExtension('e1', $extension1);
        $this->provider->addExtension('e2', $extension2);
        $this->provider->addExtension('e3', $extension3);

        $this->assertSame($localization, $this->provider->getCurrentLocalization());
    }
}
