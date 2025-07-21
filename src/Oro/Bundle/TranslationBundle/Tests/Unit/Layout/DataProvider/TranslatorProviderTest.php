<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\TranslationBundle\Layout\DataProvider\TranslatorProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Translator;

class TranslatorProviderTest extends TestCase
{
    private Translator&MockObject $translator;
    private TranslatorProvider $translatorProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->translator = $this->createMock(Translator::class);

        $this->translatorProvider = new TranslatorProvider($this->translator);
    }

    public function testGetTrans(): void
    {
        $id = 'test_key';
        $parameters = ['test_param' => 'test_value'];
        $domain = 'test_domain';
        $locale = 'test_locale';
        $data = 'data';

        $this->translator->expects(self::once())
            ->method('trans')
            ->with($id, $parameters, $domain, $locale)
            ->willReturn($data);

        self::assertEquals($data, $this->translatorProvider->getTrans($id, $parameters, $domain, $locale));
    }

    public function testGetLocale(): void
    {
        $locale = 'test_locale';

        $this->translator->expects(self::once())
            ->method('getLocale')
            ->willReturn($locale);

        self::assertEquals($locale, $this->translatorProvider->getLocale());
    }
}
