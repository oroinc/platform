<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Provider;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\CurrentLocalizationProvider;
use Oro\Bundle\LocaleBundle\Provider\LocalizationScopeCriteriaProvider;

class LocalizationScopeCriteriaProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var LocalizationScopeCriteriaProvider
     */
    protected $provider;

    /**
     * @var CurrentLocalizationProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $currentLocalizationProvider;

    protected function setUp()
    {
        $this->currentLocalizationProvider = $this->getMockBuilder(CurrentLocalizationProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new LocalizationScopeCriteriaProvider($this->currentLocalizationProvider);
    }

    public function testGetCriteriaForCurrentScope()
    {
        $localization = new Localization();
        $this->currentLocalizationProvider
            ->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $actual = $this->provider->getCriteriaForCurrentScope();

        $this->assertEquals(['localization' => $localization], $actual);
    }

    /**
     * @dataProvider contextDataProvider
     *
     * @param mixed $context
     * @param array $expected
     */
    public function testGetCriteria($context, array $expected)
    {
        $this->assertEquals($expected, $this->provider->getCriteriaByContext($context));
    }

    /**
     * @return array
     */
    public function contextDataProvider()
    {
        $localization = new Localization();
        $localizationAware = new \stdClass();
        $localizationAware->localization = $localization;

        return [
            'array_context_with_localization_key' => [
                'context' => ['localization' => $localization],
                'criteria' => ['localization' => $localization],
            ],
            'array_context_without_localization_key' => [
                'context' => [],
                'criteria' => [],
            ],
            'object_context_localization_aware' => [
                'context' => $localizationAware,
                'criteria' => ['localization' => $localization],
            ],
            'object_context_not_localization_aware' => [
                'context' => new \stdClass(),
                'criteria' => [],
            ],
        ];
    }

    public function testGetCriteriaValueType()
    {
        $this->assertEquals(Localization::class, $this->provider->getCriteriaValueType());
    }
}
