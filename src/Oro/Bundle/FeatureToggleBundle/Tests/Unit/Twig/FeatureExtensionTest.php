<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Twig;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\Twig\FeatureExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class FeatureExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var FeatureExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $container = self::getContainerBuilder()
            ->add(FeatureChecker::class, $this->featureChecker)
            ->getContainer($this);

        $this->extension = new FeatureExtension($container);
    }

    public function testIsFeatureEnabled()
    {
        $feature = 'test';
        $scopeIdentifier = 1;

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with($feature, $scopeIdentifier)
            ->willReturn(true);

        $this->assertTrue(
            self::callTwigFunction($this->extension, 'feature_enabled', [$feature, $scopeIdentifier])
        );
    }

    public function testIsResourceEnabled()
    {
        $resource = 'resource';
        $resourceType = 'type';
        $scopeIdentifier = 2;

        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with($resource, $resourceType, $scopeIdentifier)
            ->willReturn(true);

        $this->assertTrue(
            self::callTwigFunction(
                $this->extension,
                'feature_resource_enabled',
                [$resource, $resourceType, $scopeIdentifier]
            )
        );
    }
}
