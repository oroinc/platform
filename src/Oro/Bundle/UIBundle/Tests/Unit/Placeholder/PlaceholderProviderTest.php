<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Placeholder;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\UIBundle\Placeholder\PlaceholderProvider;
use Oro\Component\Config\Resolver\ResolverInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class PlaceholderProviderTest extends \PHPUnit\Framework\TestCase
{
    const TEST_PLACEHOLDER = 'test_placeholder';

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ResolverInterface
     */
    protected $resolver;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|FeatureChecker
     */
    protected $featureChecker;

    protected function setUp()
    {
        $this->resolver       = $this->createMock('Oro\Component\Config\Resolver\ResolverInterface');
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->featureChecker = $this->getMockBuilder('Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker')
            ->disableOriginalConstructor()
            ->getMock();
        $this->featureChecker->expects($this->any())
            ->method('isResourceEnabled')
            ->will($this->returnValue(true));
    }

    public function testOnlyTemplateDefined()
    {
        $items = ['placeholder_item' => [
                'template' => 'template'
        ]];

        $variables = ['foo' => 'bar'];

        $provider = $this->createProvider($items);

        $this->resolver->expects($this->at(0))
            ->method('resolve')
            ->with($items['placeholder_item'], $variables)
            ->will($this->returnValue($items['placeholder_item']));
        $actual = $provider->getPlaceholderItems(self::TEST_PLACEHOLDER, $variables);

        $this->assertSame(
            [['template' => 'template']],
            $actual
        );
    }

    public function testTemplateAndDataDefined()
    {
        $items = ['placeholder_item' => [
            'template' => 'template',
            'data' => '@service->getData($entity$)'
        ]];

        $variables = ['foo' => 'bar'];

        $provider = $this->createProvider($items);
        $this->resolver->expects($this->at(0))
            ->method('resolve')
            ->with($items['placeholder_item'], $variables)
            ->will($this->returnValue($items['placeholder_item']));

        $actual = $provider->getPlaceholderItems(self::TEST_PLACEHOLDER, $variables);

        $this->assertSame(
            [$items['placeholder_item']],
            $actual
        );
    }

    public function testApplicableStringConditionSuccess()
    {
        $items = ['placeholder_item' => [
            'template' => 'template',
            'applicable' => '@service1->isApplicable($entity$)'
        ]];

        $variables = ['foo' => 'bar'];

        $provider = $this->createProvider($items);
        $this->resolver->expects($this->at(0))
            ->method('resolve')
            ->with(['applicable' => $items['placeholder_item']['applicable']], $variables)
            ->will($this->returnValue(['applicable' => true]));
        unset($items['placeholder_item']['applicable']);
        $this->resolver->expects($this->at(1))
            ->method('resolve')
            ->with($items['placeholder_item'], $variables)
            ->will($this->returnValue($items['placeholder_item']));


        $actual = $provider->getPlaceholderItems(self::TEST_PLACEHOLDER, $variables);

        $this->assertSame(
            [$items['placeholder_item']],
            $actual
        );
    }

    public function testApplicableArrayConditionsSuccess()
    {
        $items = ['placeholder_item' => [
            'template' => 'template',
            'applicable' => ['@service1->isApplicable($entity$)', '@service1->isApplicable($entity$)']
        ]];

        $variables = ['foo' => 'bar'];

        $provider = $this->createProvider($items);
        $this->resolver->expects($this->at(0))
            ->method('resolve')
            ->with(['applicable' => $items['placeholder_item']['applicable'][0]], $variables)
            ->will($this->returnValue(['applicable' => true]));
        $this->resolver->expects($this->at(1))
            ->method('resolve')
            ->with(['applicable' => $items['placeholder_item']['applicable'][1]], $variables)
            ->will($this->returnValue(['applicable' => true]));
        unset($items['placeholder_item']['applicable']);
        $this->resolver->expects($this->at(2))
            ->method('resolve')
            ->with($items['placeholder_item'], $variables)
            ->will($this->returnValue($items['placeholder_item']));


        $actual = $provider->getPlaceholderItems(self::TEST_PLACEHOLDER, $variables);

        $this->assertSame(
            [$items['placeholder_item']],
            $actual
        );
    }

    public function testApplicableArrayConditionsFail()
    {
        $items = ['placeholder_item' => [
            'template' => 'template',
            'applicable' => ['@service1->isApplicable($entity$)', '@service1->isApplicable($entity$)']
        ]];

        $variables = ['foo' => 'bar'];

        $provider = $this->createProvider($items);
        $this->resolver->expects($this->at(0))
            ->method('resolve')
            ->with(['applicable' => $items['placeholder_item']['applicable'][0]], $variables)
            ->will($this->returnValue(['applicable' => false]));


        $actual = $provider->getPlaceholderItems(self::TEST_PLACEHOLDER, $variables);

        $this->assertSame([], $actual);
    }

    public function testAclConditionStringSuccess()
    {
        $items = ['placeholder_item' => [
            'template' => 'template',
            'acl' => 'acl_ancestor'
        ]];

        $variables = ['foo' => 'bar'];

        $provider = $this->createProvider($items);
        $this->authorizationChecker->expects($this->at(0))
            ->method('isGranted')
            ->with('acl_ancestor')
            ->will($this->returnValue(true));


        $actual = $provider->getPlaceholderItems(self::TEST_PLACEHOLDER, $variables);
        unset($items['placeholder_item']['acl']);
        $this->assertSame([], $actual);
    }

    public function testAclConditionStringFail()
    {
        $items = ['placeholder_item' => [
            'template' => 'template',
            'acl' => 'acl_ancestor'
        ]];

        $variables = ['foo' => 'bar'];

        $provider = $this->createProvider($items);
        $this->authorizationChecker->expects($this->at(0))
            ->method('isGranted')
            ->with('acl_ancestor')
            ->will($this->returnValue(false));


        $actual = $provider->getPlaceholderItems(self::TEST_PLACEHOLDER, $variables);
        unset($items['placeholder_item']['acl']);
        $this->assertSame([], $actual);
    }

    public function testAclConditionArraySuccess()
    {
        $items = ['placeholder_item' => [
            'template' => 'template',
            'acl' => ['acl_ancestor1', 'acl_ancestor2']
        ]];

        $variables = ['foo' => 'bar'];

        $provider = $this->createProvider($items);

        $this->authorizationChecker->expects($this->at(0))
            ->method('isGranted')
            ->with('acl_ancestor1')
            ->will($this->returnValue(true));
        $this->authorizationChecker->expects($this->at(1))
            ->method('isGranted')
            ->with('acl_ancestor2')
            ->will($this->returnValue(true));
        unset($items['placeholder_item']['acl']);
        $this->resolver->expects($this->at(0))
            ->method('resolve')
            ->with($items['placeholder_item'], $variables)
            ->will($this->returnValue($items['placeholder_item']));


        $actual = $provider->getPlaceholderItems(self::TEST_PLACEHOLDER, $variables);
        unset($items['placeholder_item']['acl']);
        $this->assertSame([$items['placeholder_item']], $actual);
    }

    public function testAclConditionArrayFail()
    {
        $items = ['placeholder_item' => [
            'template' => 'template',
            'acl' => ['acl_ancestor1', 'acl_ancestor2']
        ]];

        $variables = ['foo' => 'bar'];

        $provider = $this->createProvider($items);
        $this->authorizationChecker->expects($this->at(0))
            ->method('isGranted')
            ->with('acl_ancestor1')
            ->will($this->returnValue(false));


        $actual = $provider->getPlaceholderItems(self::TEST_PLACEHOLDER, $variables);
        unset($items['placeholder_item']['acl']);
        $this->assertSame([], $actual);
    }

    /**
     * @param array $items
     * @return PlaceholderProvider
     */
    protected function createProvider(array $items)
    {
        $placeholders = [
            'placeholders' => [
                self::TEST_PLACEHOLDER => [
                    'items' => array_keys($items)
                ]
            ],
            'items' => $items
        ];

        return new PlaceholderProvider(
            $placeholders,
            $this->resolver,
            $this->authorizationChecker,
            $this->featureChecker
        );
    }
}
