<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Twig;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\Twig\FeatureExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FeatureExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private FeatureChecker&MockObject $featureChecker;
    private FeatureExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $container = self::getContainerBuilder()
            ->add(FeatureChecker::class, $this->featureChecker)
            ->getContainer($this);

        $this->extension = new FeatureExtension($container);
    }

    public function testIsFeatureEnabled(): void
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

    public function testIsResourceEnabled(): void
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
