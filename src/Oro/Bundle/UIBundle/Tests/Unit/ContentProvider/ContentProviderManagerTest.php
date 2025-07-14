<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\ContentProvider;

use Oro\Bundle\UIBundle\ContentProvider\ContentProviderInterface;
use Oro\Bundle\UIBundle\ContentProvider\ContentProviderManager;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ContentProviderManagerTest extends TestCase
{
    private ContentProviderInterface&MockObject $provider1;
    private ContentProviderInterface&MockObject $provider2;
    private ContentProviderManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider1 = $this->createMock(ContentProviderInterface::class);
        $this->provider2 = $this->createMock(ContentProviderInterface::class);

        $container = TestContainerBuilder::create()
            ->add('provider1', $this->provider1)
            ->add('provider2', $this->provider2)
            ->getContainer($this);

        $this->manager = new ContentProviderManager(
            ['provider1', 'provider2'],
            $container,
            ['provider1']
        );
    }

    public function testGetContentProviderNames(): void
    {
        $this->assertEquals(
            ['provider1', 'provider2'],
            $this->manager->getContentProviderNames()
        );
    }

    public function testDisableContentProvider(): void
    {
        $this->manager->disableContentProvider('provider1');

        $this->provider1->expects($this->never())
            ->method('getContent');
        $this->provider2->expects($this->never())
            ->method('getContent');

        $this->assertEquals(
            [],
            $this->manager->getContent()
        );
    }

    public function testDisableContentProviderForAlreadyDisabledProvider(): void
    {
        $this->manager->disableContentProvider('provider2');

        $this->provider1->expects($this->once())
            ->method('getContent')
            ->willReturn('content1');
        $this->provider2->expects($this->never())
            ->method('getContent');

        $this->assertEquals(
            ['provider1' => 'content1'],
            $this->manager->getContent()
        );
    }

    public function testEnableContentProvider(): void
    {
        $this->manager->enableContentProvider('provider2');

        $this->provider1->expects($this->once())
            ->method('getContent')
            ->willReturn('content1');
        $this->provider2->expects($this->once())
            ->method('getContent')
            ->willReturn('content2');

        $this->assertEquals(
            ['provider1' => 'content1', 'provider2' => 'content2'],
            $this->manager->getContent()
        );
    }

    public function testEnableContentProviderForAlreadyEnabledProvider(): void
    {
        $this->manager->enableContentProvider('provider1');

        $this->provider1->expects($this->once())
            ->method('getContent')
            ->willReturn('content1');
        $this->provider2->expects($this->never())
            ->method('getContent');

        $this->assertEquals(
            ['provider1' => 'content1'],
            $this->manager->getContent()
        );
    }

    public function testReset(): void
    {
        $this->manager->enableContentProvider('provider2');
        $this->manager->reset();

        $this->provider1->expects($this->once())
            ->method('getContent')
            ->willReturn('content1');
        $this->provider2->expects($this->never())
            ->method('getContent');

        $this->assertEquals(
            ['provider1' => 'content1'],
            $this->manager->getContent()
        );
    }

    public function testGetContentForSpecificName(): void
    {
        $this->provider1->expects($this->once())
            ->method('getContent')
            ->willReturn('content1');
        $this->provider2->expects($this->never())
            ->method('getContent');

        $this->assertEquals(
            ['provider1' => 'content1'],
            $this->manager->getContent(['provider1'])
        );
    }

    public function testGetContentForSpecificNamesIncludingNameOfDisabledProvider(): void
    {
        $this->provider1->expects($this->once())
            ->method('getContent')
            ->willReturn('content1');
        $this->provider2->expects($this->once())
            ->method('getContent')
            ->willReturn('content2');

        $this->assertEquals(
            ['provider1' => 'content1', 'provider2' => 'content2'],
            $this->manager->getContent(['provider1', 'provider2'])
        );
    }
}
