<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Manager;

use Oro\Bundle\DraftBundle\Helper\DraftHelper;
use Oro\Bundle\DraftBundle\Manager\Publisher;
use Oro\Bundle\DraftBundle\Tests\Unit\Stub\DraftableEntityStub;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;

class PublisherTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DraftHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $draftHelper;

    /**
     * @var Publisher
     */
    private $publisher;

    protected function setUp(): void
    {
        $this->draftHelper = $this->createMock(DraftHelper::class);

        $this->publisher = new Publisher($this->draftHelper);
    }

    public function testGetDraftableProperties(): void
    {
        $source = new DraftableEntityStub();
        $source->title = 'Page title';
        $source->content = '<h1>Page</h1>';

        $draft = new DraftableEntityStub();
        $draft->title = 'Draft title';
        $draft->content = '<h1>Draft</h1>';
        $draft->setDraftUuid(UUIDGenerator::v4());
        $draft->setDraftSource($source);

        $this->draftHelper->expects($this->once())
            ->method('getDraftableProperties')
            ->willReturn(['content', 'title']);

        $result = $this->publisher->create($draft);

        $this->assertEquals('Draft title', $result->title);
        $this->assertEquals('<h1>Draft</h1>', $result->content);
    }
}
