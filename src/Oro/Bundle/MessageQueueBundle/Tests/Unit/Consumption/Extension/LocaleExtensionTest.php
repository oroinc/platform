<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Gedmo\Translatable\TranslatableListener;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\MessageQueueBundle\Consumption\Extension\LocaleExtension;
use Oro\Component\MessageQueue\Consumption\Context;

class LocaleExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject */
    private $localeSettings;

    /** @var TranslatableListener */
    private $translatableListener;

    /** @var LocaleExtension */
    private $localeExtension;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->localeSettings = $this->createMock(LocaleSettings::class);
        $this->translatableListener = new TranslatableListener();
        $this->localeExtension = new LocaleExtension($this->localeSettings, $this->translatableListener);
    }

    public function testOnStart(): void
    {
        $this->localeSettings
            ->expects($this->once())
            ->method('getLocale')
            ->willReturn('fr');

        /** @var Context $context */
        $context = $this->createMock(Context::class);
        $this->localeExtension->onStart($context);

        $this->assertEquals('fr', \Locale::getDefault());
    }

    public function testOnPreReceived(): void
    {
        $this->localeSettings
            ->expects($this->once())
            ->method('getLanguage')
            ->willReturn('FR_fr');

        /** @var Context $context */
        $context = $this->createMock(Context::class);
        $this->localeExtension->onPreReceived($context);

        $this->assertEquals('FR_fr', $this->translatableListener->getListenerLocale());
    }
}
