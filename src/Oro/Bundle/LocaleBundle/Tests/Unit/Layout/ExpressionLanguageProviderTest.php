<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Layout;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\LocaleBundle\Layout\ExpressionLanguageProvider;

class ExpressionLanguageProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $localizationHelper;

    /** @var ExpressionLanguageProvider */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->localizationHelper = $this->getMockBuilder(LocalizationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new ExpressionLanguageProvider($this->localizationHelper);
    }

    public function testGetFunctions()
    {
        /* @var $functions ExpressionFunction[] */
        $functions = $this->provider->getFunctions();

        $this->assertCount(1, $functions);

        $this->assertInstanceOf(ExpressionFunction::class, $functions[0]);
        $this->assertEquals('localized_value', $functions[0]->getName());
    }
}
