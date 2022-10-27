<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Metadata;

use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationProvider;
use Oro\Bundle\SecurityBundle\Metadata\ActionSecurityMetadata;
use Oro\Bundle\SecurityBundle\Metadata\ActionSecurityMetadataProvider;
use Oro\Bundle\SecurityBundle\Metadata\Label;

class ActionSecurityMetadataProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $annotationProvider;

    /** @var ActionSecurityMetadataProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->annotationProvider = $this->createMock(AclAnnotationProvider::class);

        $this->provider = new ActionSecurityMetadataProvider(
            $this->annotationProvider
        );
    }

    public function testIsKnownActionForKnownAction()
    {
        $this->annotationProvider->expects($this->once())
            ->method('findAnnotationById')
            ->with('SomeAction')
            ->willReturn(new AclAnnotation(['id' => 'SomeAction', 'type' => 'action']));

        $this->assertTrue($this->provider->isKnownAction('SomeAction'));
    }

    public function testIsKnownActionForNotActionAclAnnotationId()
    {
        $this->annotationProvider->expects($this->once())
            ->method('findAnnotationById')
            ->with('SomeAclAnnotationId')
            ->willReturn(new AclAnnotation(['id' => 'SomeAclAnnotationId', 'type' => 'entity']));

        $this->assertFalse($this->provider->isKnownAction('SomeAclAnnotationId'));
    }

    public function testIsKnownActionForUnknownAction()
    {
        $this->annotationProvider->expects($this->once())
            ->method('findAnnotationById')
            ->with('UnknownAction')
            ->willReturn(null);

        $this->assertFalse($this->provider->isKnownAction('UnknownAction'));
    }

    public function testGetActions()
    {
        $this->annotationProvider->expects($this->once())
            ->method('getAnnotations')
            ->with('action')
            ->willReturn([
                new AclAnnotation([
                    'id'          => 'test',
                    'type'        => 'action',
                    'group_name'  => 'TestGroup',
                    'label'       => 'TestLabel',
                    'description' => 'TestDescription',
                    'category'    => 'TestCategory'
                ])
            ]);

        $action = new ActionSecurityMetadata(
            'test',
            'TestGroup',
            new Label('TestLabel'),
            new Label('TestDescription'),
            'TestCategory'
        );

        $actions = $this->provider->getActions();
        $this->assertCount(1, $actions);
        $this->assertEquals($action, $actions[0]);

        // call with local cache
        $actions = $this->provider->getActions();
        $this->assertCount(1, $actions);
        $this->assertEquals($action, $actions[0]);
    }
}
