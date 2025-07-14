<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Menu;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\Menu\FeatureAwareMenuFactoryExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FeatureAwareMenuFactoryExtensionTest extends TestCase
{
    private FeatureChecker&MockObject $featureChecker;
    private FeatureAwareMenuFactoryExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->extension = new FeatureAwareMenuFactoryExtension($this->featureChecker);
    }

    public function testBuildOptionsChangeToNotAllowed(): void
    {
        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with('route_name', 'routes')
            ->willReturn(false);

        $options = $this->extension->buildOptions(
            [
                'extras' => [
                    'isAllowed' => true,
                ],
                'route' => 'route_name',
            ]
        );

        $this->assertFalse($options['extras']['isAllowed']);
    }

    /**
     * @dataProvider optionsDataProvider
     */
    public function testBuildOptionsNoChanges(array $options): void
    {
        $this->featureChecker->expects($this->never())
            ->method('isResourceEnabled');

        $this->extension->buildOptions($options);
    }

    public function optionsDataProvider(): array
    {
        return [
            [
                'options' => [],
            ],
            [
                'options' => [
                    'extras' => [
                        'isAllowed' => false,
                    ],
                    'route' => 'route_name',
                ],
            ],
            [
                'options' => [
                    'extras' => [
                        'isAllowed' => true,
                    ],
                ],
            ],
        ];
    }
}
