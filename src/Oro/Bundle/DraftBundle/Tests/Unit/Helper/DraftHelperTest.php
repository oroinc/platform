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
    /**
     * @var RequestStack|\PHPUnit\Framework\MockObject\MockObject
     */
    private $requestStack;

    /**
     * @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $draftProvider;

    /**
     * @var DraftHelper
     */
    private $helper;

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

    public function testGetDraftableProperties(): void
    {
        $source = new DraftableEntityStub();
        $className = DraftableEntityStub::class;

        $this->draftProvider
            ->expects($this->once())
            ->method('getConfigs')
            ->with($className, true)
            ->willReturn([
                new Config(new FieldConfigId('draftable', $className, 'content'), ['draftable' => true]),
                new Config(new FieldConfigId('draftable', $className, 'titles'), ['draftable' => false]),
            ]);

        $this->assertEquals(['content'], $this->helper->getDraftableProperties($source));
    }
}
