<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Placeholder;

use Oro\Bundle\DraftBundle\Placeholder\PlaceholderFilter;
use Oro\Bundle\DraftBundle\Tests\Unit\Stub\DraftableEntityStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class PlaceholderFilterTest extends TestCase
{
    private PlaceholderFilter $placeholder;
    private AuthorizationCheckerInterface&MockObject $authorizationChecker;

    #[\Override]
    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->placeholder = new PlaceholderFilter($this->authorizationChecker);
    }

    /**
     * @dataProvider permissionDataProvider
     */
    public function testIsApplicable(bool $isGranted): void
    {
        $source = new DraftableEntityStub();
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('CREATE_DRAFT', $source)
            ->willReturn($isGranted);

        $hasAccess = $this->placeholder->isApplicable($source);
        $this->assertEquals($isGranted, $hasAccess);
    }

    public function permissionDataProvider(): array
    {
        return [
            'Is granted' => [
                'isGranted' => true,
            ],
            'Is denied' => [
                'isGranted' => false,
            ]
        ];
    }
}
