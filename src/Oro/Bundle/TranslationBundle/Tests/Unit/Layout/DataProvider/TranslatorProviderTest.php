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

    public function testTrans()
    {
        $this->translator->expects($this->atLeastOnce())->method('trans');
        $this->translatorProvider->getTrans('key');
    }

    public function testTransChoice()
    {
        $this->translator->expects($this->atLeastOnce())->method('transChoice');
        $this->translatorProvider->getTransChoice('key', 1);
    }

    public function testGetLocale()
    {
        $this->translator->expects($this->once())->method('getLocale');
        $this->translatorProvider->getLocale();
    }
}
