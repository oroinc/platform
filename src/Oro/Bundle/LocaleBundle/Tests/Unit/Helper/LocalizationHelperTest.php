<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Helper;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProvider;
use Oro\Bundle\LocaleBundle\Tests\Unit\Entity\FallbackTrait;

class LocalizationHelperTest extends \PHPUnit_Framework_TestCase
{
    use FallbackTrait;

    /** @var LocalizationProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $localizationProvider;

    /** @var LocalizationHelper */
    protected $helper;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->localizationProvider = $this->getMockBuilder(LocalizationProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new LocalizationHelper($this->localizationProvider);
    }

    public function testGetLocalizedValue()
    {
        $this->assertFallbackValue($this->helper, 'getLocalizedValue');
    }
}
