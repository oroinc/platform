<?php
namespace Oro\Bundle\TranslationBundle\Tests\Unit\Layput\DataProvider;

use Oro\Bundle\TranslationBundle\Layout\DataProvider\TranslatorProvider;
use Symfony\Component\Translation\TranslatorInterface;

class TranslatorProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var TranslatorProvider */
    protected $provider;

    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->provider = new TranslatorProvider($this->translator);
    }

    public function testGetLocale()
    {
        $locale = 'test_locale';

        $this->translator->expects($this->once())->method('getLocale')->willReturn($locale);

        $this->assertEquals($locale, $this->provider->getLocale());
    }
}
