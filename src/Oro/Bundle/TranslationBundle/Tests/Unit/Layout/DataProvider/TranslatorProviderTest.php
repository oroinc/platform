<?php
namespace Oro\Bundle\TranslationBundle\Tests\Unit\Layput\DataProvider;

class TranslatorProviderTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
    }
}
