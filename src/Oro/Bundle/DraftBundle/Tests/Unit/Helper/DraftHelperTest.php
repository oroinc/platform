<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Helper;

use Oro\Bundle\DraftBundle\Helper\DraftHelper;
use Oro\Bundle\DraftBundle\Tests\Unit\Stub\DraftableEntityStub;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\UIBundle\Route\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class DraftHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var DraftHelper */
    private $helper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|RequestStack */
    private $requestStack;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->helper = new DraftHelper($this->requestStack);
    }

    public function testIsSaveAsDraftAction(): void
    {
        $request = new Request([], [Router::ACTION_PARAMETER => DraftHelper::SAVE_AS_DRAFT_ACTION]);
        $this->requestStack
            ->expects($this->exactly(2))
            ->method('getMasterRequest')
            ->willReturn($request);

        $this->assertTrue($this->helper->isSaveAsDraftAction());
    }

    public function testIsAnyAction(): void
    {
        $request = new Request([], [Router::ACTION_PARAMETER => 'any_action']);
        $this->requestStack
            ->expects($this->exactly(2))
            ->method('getMasterRequest')
            ->willReturn($request);

        $this->assertFalse($this->helper->isSaveAsDraftAction());
    }

    public function testIsDraft(): void
    {
        $source = new DraftableEntityStub();
        $this->assertFalse($this->helper->isDraft($source));

        $draftSource = new DraftableEntityStub();
        $source->setDraftUuid(UUIDGenerator::v4());
        $source->setDraftSource($draftSource);
        $this->assertTrue(DraftHelper::isDraft($source));
    }
}
