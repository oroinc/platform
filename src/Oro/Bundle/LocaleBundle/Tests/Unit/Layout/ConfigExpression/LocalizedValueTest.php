<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Layout\ConfigExpression;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\LocaleBundle\Layout\ConfigExpression\LocalizedValue;

class LocalizedValueTest extends \PHPUnit_Framework_TestCase
{
    /** @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $localizationHelper;

    /** @var LocalizedValue */
    protected $func;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->localizationHelper = $this->getMockBuilder(LocalizationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->func = new LocalizedValue($this->localizationHelper);
    }

    public function testGetName()
    {
        $this->assertEquals(LocalizedValue::NAME, $this->func->getName());
    }

    public function testEvaluate()
    {
        $localizedValue = (new LocalizedFallbackValue())
            ->setString('Value1');

        $this->localizationHelper->expects($this->once())
            ->method('getLocalizedValue')
            ->willReturn($localizedValue);

        $this->func->initialize([new ArrayCollection()]);

        $this->assertSame($localizedValue, $this->func->evaluate([]));
    }
}
