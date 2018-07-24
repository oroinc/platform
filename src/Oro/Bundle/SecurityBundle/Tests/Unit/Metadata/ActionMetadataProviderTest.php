<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Metadata;

use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
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
        $this->cache = $this->getMockForAbstractClass(
            'Doctrine\Common\Cache\CacheProvider',
            array(),
            '',
            false,
            true,
            true,
            array('fetch', 'save', 'delete', 'deleteAll')
        );

        $this->annotationProvider = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Metadata\AclAnnotationProvider')
            ->disableOriginalConstructor()
            ->getMock();

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
        $this->cache->expects($this->any())
            ->method('fetch')
            ->with(ActionMetadataProvider::CACHE_KEY)
            ->will($this->returnValue(array('SomeAction' => new ActionMetadata())));

        $this->assertTrue($this->provider->isKnownAction('SomeAction'));
        $this->assertFalse($this->provider->isKnownAction('UnknownAction'));
    }

    public function testGetActions()
    {
        $this->annotationProvider->expects($this->once())
            ->method('getAnnotations')
            ->with($this->equalTo('action'))
            ->will(
                $this->returnValue(
                    array(
                        new AclAnnotation(
                            array(
                                'id' => 'test',
                                'type' => 'action',
                                'group_name' => 'TestGroup',
                                'label' => 'TestLabel',
                                'description' => 'TestDescription',
                                'category' => 'TestCategory',
                            )
                        )
                    )
                )
            );

        $action = new ActionMetadata(
            'test',
            'TestGroup',
            'translated: TestLabel',
            'translated: TestDescription',
            'TestCategory'
        );

        $this->cache->expects($this->at(0))
            ->method('fetch')
            ->with(ActionMetadataProvider::CACHE_KEY)
            ->will($this->returnValue(false));
        $this->cache->expects($this->at(2))
            ->method('fetch')
            ->with(ActionMetadataProvider::CACHE_KEY)
            ->will($this->returnValue(array('test' => $action)));
        $this->cache->expects($this->once())
            ->method('save')
            ->with(ActionMetadataProvider::CACHE_KEY, array('test' => $action));

        // call without cache
        $actions = $this->provider->getActions();
        $this->assertCount(1, $actions);
        $this->assertEquals($action, $actions[0]);

        // call with local cache
        $actions = $this->provider->getActions();
        $this->assertCount(1, $actions);
        $this->assertEquals($action, $actions[0]);

        // call with cache
        $provider = new ActionMetadataProvider($this->annotationProvider, $this->translator, $this->cache);
        $actions = $provider->getActions();
        $this->assertCount(1, $actions);
        $this->assertEquals($action, $actions[0]);
    }

    public function testCache()
    {
        // Called when: warmUpCache, isKnownAction, warmUpCache
        $this->annotationProvider->expects($this->exactly(3))
            ->method('getAnnotations')
            ->with($this->equalTo('action'))
            ->will($this->returnValue(array()));
        // First warmUpCache
        $this->cache->expects($this->at(0))
            ->method('save')
            ->with(ActionMetadataProvider::CACHE_KEY);
        // clearCache
        $this->cache->expects($this->at(1))
            ->method('delete')
            ->with(ActionMetadataProvider::CACHE_KEY);
        // First isKnownAction
        $this->cache->expects($this->at(2))
            ->method('fetch')
            ->with(ActionMetadataProvider::CACHE_KEY);
        $this->cache->expects($this->at(3))
            ->method('save')
            ->with(ActionMetadataProvider::CACHE_KEY);
        // Second warmUpCache
        $this->cache->expects($this->at(4))
            ->method('save')
            ->with(ActionMetadataProvider::CACHE_KEY);

        $this->provider->warmUpCache();
        $this->provider->clearCache();
        $this->assertFalse($this->provider->isKnownAction('unknown'));
        $this->provider->warmUpCache();
        $this->assertFalse($this->provider->isKnownAction('unknown'));
    }
}
