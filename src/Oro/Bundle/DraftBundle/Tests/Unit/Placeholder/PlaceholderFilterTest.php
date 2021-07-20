<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Placeholder;

use Oro\Bundle\DraftBundle\Placeholder\PlaceholderFilter;
use Oro\Bundle\DraftBundle\Tests\Unit\Stub\DraftableEntityStub;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class PlaceholderFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var PlaceholderFilter */
    private $placeholder;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AuthorizationCheckerInterface */
    private $authorizationChecker;

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
        $this->authorizationChecker
            ->expects($this->once())
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
