<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Provider;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\Provider\ApiExclusionProvider;

class ApiExclusionProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FeatureChecker|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $featureChecker;

    /**
     * @var ApiExclusionProvider
     */
    protected $apiExclusionProvider;

    protected function setUp()
    {
        $this->featureChecker = $this->getMockBuilder(FeatureChecker::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->apiExclusionProvider = new ApiExclusionProvider($this->featureChecker);
    }

    /**
     * @dataProvider isIgnoredEntityDataProvider
     *
     * @param $isResourceEnabled
     * @param $expected
     */
    public function testIsIgnoredEntity($isResourceEnabled, $expected)
    {
        $className = 'Oro\Bundle\SomeBundle\Entity\SomeEntity';

        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with($className, ApiExclusionProvider::API_RESOURCE_KEY)
            ->willReturn($isResourceEnabled);

        $this->assertEquals($expected, $this->apiExclusionProvider->isIgnoredEntity($className));
    }

    /**
     * @return array
     */
    public function isIgnoredEntityDataProvider()
    {
        return [
            'ignored' => [
                'isResourceEnabled' => false,
                'expected' => true
            ],
            'not ignored' => [
                'isResourceEnabled' => true,
                'expected' => false
            ]
        ];
    }
}
