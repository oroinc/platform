<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Metadata;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationProvider;
use Oro\Bundle\SecurityBundle\Metadata\ActionMetadata;
use Oro\Bundle\SecurityBundle\Metadata\ActionMetadataProvider;
use Symfony\Component\Translation\TranslatorInterface;

class ActionMetadataProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $cache;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $annotationProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var ActionMetadataProvider */
    protected $provider;

    protected function setUp()
    {
        $this->cache = $this->createMock(CacheProvider::class);
        $this->annotationProvider = $this->createMock(AclAnnotationProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function ($value) {
                return 'translated: ' . $value;
            });

        $this->provider = new ActionMetadataProvider(
            $this->annotationProvider,
            $this->translator,
            $this->cache
        );
    }

    public function testIsKnownAction()
    {
        $cacheTimestamp = 10;
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with('data')
            ->willReturn([$cacheTimestamp, ['SomeAction' => new ActionMetadata()]]);
        $this->annotationProvider->expects($this->once())
            ->method('isCacheFresh')
            ->with($cacheTimestamp)
            ->willReturn(true);

        $this->assertTrue($this->provider->isKnownAction('SomeAction'));
        $this->assertFalse($this->provider->isKnownAction('UnknownAction'));
    }

    public function testGetActionsWhenNoCache()
    {
        $configTimestamp = 20;

        $this->annotationProvider->expects($this->never())
            ->method('isCacheFresh');
        $this->annotationProvider->expects($this->once())
            ->method('getCacheTimestamp')
            ->willReturn($configTimestamp);
        $this->annotationProvider->expects($this->once())
            ->method('getAnnotations')
            ->with('action')
            ->willReturn([
                new AclAnnotation([
                    'id' => 'test',
                    'type' => 'action',
                    'group_name' => 'TestGroup',
                    'label' => 'TestLabel',
                    'description' => 'TestDescription',
                    'category' => 'TestCategory',
                ])
            ]);

        $action = new ActionMetadata(
            'test',
            'TestGroup',
            'translated: TestLabel',
            'translated: TestDescription',
            'TestCategory'
        );

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with('data')
            ->willReturn(false);
        $this->cache->expects($this->once())
            ->method('save')
            ->with('data', [$configTimestamp, ['test' => $action]]);

        $actions = $this->provider->getActions();
        $this->assertCount(1, $actions);
        $this->assertEquals($action, $actions[0]);

        // call with local cache
        $actions = $this->provider->getActions();
        $this->assertCount(1, $actions);
        $this->assertEquals($action, $actions[0]);
    }

    public function testGetActionsWhenHasCache()
    {
        $cacheTimestamp = 10;

        $this->annotationProvider->expects($this->once())
            ->method('isCacheFresh')
            ->with($cacheTimestamp)
            ->willReturn(true);
        $this->annotationProvider->expects($this->never())
            ->method('getCacheTimestamp');
        $this->annotationProvider->expects($this->never())
            ->method('getAnnotations');

        $action = new ActionMetadata(
            'test',
            'TestGroup',
            'translated: TestLabel',
            'translated: TestDescription',
            'TestCategory'
        );

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with('data')
            ->willReturn([$cacheTimestamp, ['test' => $action]]);
        $this->cache->expects($this->never())
            ->method('save');

        // call without cache
        $actions = $this->provider->getActions();
        $this->assertCount(1, $actions);
        $this->assertEquals($action, $actions[0]);

        // call with local cache
        $actions = $this->provider->getActions();
        $this->assertCount(1, $actions);
        $this->assertEquals($action, $actions[0]);
    }

    public function testGetActionsWhenHasCacheButConfigurationWasChanged()
    {
        $cacheTimestamp = 10;
        $configTimestamp = 20;

        $this->annotationProvider->expects($this->once())
            ->method('isCacheFresh')
            ->with($cacheTimestamp)
            ->willReturn(false);
        $this->annotationProvider->expects($this->once())
            ->method('getCacheTimestamp')
            ->willReturn($configTimestamp);
        $this->annotationProvider->expects($this->once())
            ->method('getAnnotations')
            ->with('action')
            ->willReturn([
                new AclAnnotation([
                    'id' => 'test',
                    'type' => 'action',
                    'group_name' => 'TestGroup',
                    'label' => 'TestLabel',
                    'description' => 'TestDescription',
                    'category' => 'TestCategory',
                ])
            ]);

        $action = new ActionMetadata(
            'test',
            'TestGroup',
            'translated: TestLabel',
            'translated: TestDescription',
            'TestCategory'
        );

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with('data')
            ->willReturn([$cacheTimestamp, ['test' => $action]]);
        $this->cache->expects($this->once())
            ->method('save')
            ->with('data', [$configTimestamp, ['test' => $action]]);

        $actions = $this->provider->getActions();
        $this->assertCount(1, $actions);
        $this->assertEquals($action, $actions[0]);

        // call with local cache
        $actions = $this->provider->getActions();
        $this->assertCount(1, $actions);
        $this->assertEquals($action, $actions[0]);
    }

    public function testCacheClear()
    {
        $this->cache->expects($this->once(1))
            ->method('delete')
            ->with('data');

        $this->provider->clearCache();
    }

    public function testWarmUpCache()
    {
        $configTimestamp = 10;

        $this->annotationProvider->expects($this->any())
            ->method('getCacheTimestamp')
            ->willReturn($configTimestamp);

        $this->annotationProvider->expects($this->once())
            ->method('getAnnotations')
            ->with('action')
            ->willReturn([]);
        $this->cache->expects($this->once())
            ->method('save')
            ->with('data', [$configTimestamp, []]);

        $this->cache->expects($this->never())
            ->method('fetch');
        $this->cache->expects($this->never())
            ->method('delete');

        $this->provider->warmUpCache();
    }
}
