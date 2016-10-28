<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Layput\DataProvider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\TranslationBundle\Layout\DataProvider\TranslatorProvider;

class TranslatorProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var  TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var TranslatorProvider */
    protected $translatorProvider;

    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->translatorProvider = new TranslatorProvider($this->translator);
    }

    protected function tearDown()
    {
        unset($this->translator, $this->translatorProvider);
    }

    public function testGetTrans()
    {
        $id = 'test_key';
        $parameters = ['test_param' => 'test_value'];
        $domain = 'test_domain';
        $locale = 'test_locale';
        $data = 'data';

        $this->translator->expects($this->once())
            ->method('trans')
            ->with($id, $parameters, $domain, $locale)
            ->willReturn($data);

        $this->assertEquals($data, $this->translatorProvider->getTrans($id, $parameters, $domain, $locale));
    }

    public function testGetTransChoice()
    {
        $id = 'test_key';
        $number = '42';
        $parameters = ['test_param' => 'test_value'];
        $domain = 'test_domain';
        $locale = 'test_locale';
        $data = 'data';

        $this->translator->expects($this->once())
            ->method('transChoice')
            ->with($id, $number, $parameters, $domain, $locale)
            ->willReturn($data);

        $this->assertEquals(
            $data,
            $this->translatorProvider->getTransChoice($id, $number, $parameters, $domain, $locale)
        );
    }

    public function testGetLocale()
    {
        $this->translator->expects($this->once())->method('getLocale');
        $this->translatorProvider->getLocale();
    }
}
