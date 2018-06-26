<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Gedmo\Translatable\TranslatableListener;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\MessageQueueBundle\Consumption\Extension\LocaleExtension;
use Oro\Component\MessageQueue\Consumption\Context;

class LocaleExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testSetLocaleOnBeforeReceive()
    {
        $localeSettings = $this->createMock(LocaleSettings::class);
        $translatableListener = new TranslatableListener();

        $localeSettings->expects($this->once())
            ->method('getLocale')
            ->willReturn('fr');

        $localeSettings->expects($this->once())
            ->method('getLanguage')
            ->willReturn('FR_fr');

        $extension = new LocaleExtension($localeSettings, $translatableListener);
        $extension->onBeforeReceive($this->createMock(Context::class));

        $this->assertEquals('fr', \Locale::getDefault());
        $this->assertEquals('FR_fr', $translatableListener->getListenerLocale());
    }
}
