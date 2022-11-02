<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Helper;

use Oro\Bundle\DraftBundle\Helper\DraftHelper;
use Oro\Bundle\DraftBundle\Tests\Unit\Stub\DraftableEntityStub;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\UIBundle\Route\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class DraftHelperTest extends \PHPUnit\Framework\TestCase
{
    private RequestStack|\PHPUnit\Framework\MockObject\MockObject $requestStack;

    private ConfigProvider|\PHPUnit\Framework\MockObject\MockObject $draftProvider;

    private DraftHelper $helper;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->draftProvider = $this->createMock(ConfigProvider::class);

        $this->helper = new DraftHelper($this->requestStack, $this->draftProvider);
    }

    public function testIsSaveAsDraftAction(): void
    {
        $request = new Request([], [Router::ACTION_PARAMETER => DraftHelper::SAVE_AS_DRAFT_ACTION]);
        $this->requestStack
            ->expects(self::exactly(2))
            ->method('getMainRequest')
            ->willReturn($request);

        self::assertTrue($this->helper->isSaveAsDraftAction());
    }

    public function testIsAnyAction(): void
    {
        $request = new Request([], [Router::ACTION_PARAMETER => 'any_action']);
        $this->requestStack
            ->expects(self::exactly(2))
            ->method('getMainRequest')
            ->willReturn($request);

        self::assertFalse($this->helper->isSaveAsDraftAction());
    }

    public function testIsDraft(): void
    {
        $source = new DraftableEntityStub();
        self::assertFalse($this->helper->isDraft($source));

        $draftSource = new DraftableEntityStub();
        $source->setDraftUuid(UUIDGenerator::v4());
        $source->setDraftSource($draftSource);
        self::assertTrue(DraftHelper::isDraft($source));
    }

    public function testGetDraftableProperties(): void
    {
        $source = new DraftableEntityStub();
        $className = DraftableEntityStub::class;

        $this->draftProvider
            ->expects(self::once())
            ->method('getConfigs')
            ->with($className, true)
            ->willReturn([
                new Config(new FieldConfigId('draftable', $className, 'content'), ['draftable' => true]),
                new Config(new FieldConfigId('draftable', $className, 'titles'), ['draftable' => false]),
            ]);

        self::assertEquals(['content'], $this->helper->getDraftableProperties($source));
    }
}
