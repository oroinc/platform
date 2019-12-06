<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Duplicator\Filter;

use Oro\Bundle\DraftBundle\Duplicator\Filter\OwnerFilter;
use Oro\Bundle\DraftBundle\Tests\Unit\Stub\DraftableEntityStub;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class OwnerFilterTest extends \PHPUnit\Framework\TestCase
{
    public function testApply(): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|TokenInterface $token */
        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(new User());

        /** @var \PHPUnit\Framework\MockObject\MockObject|TokenStorage $tokenStorage */
        $tokenStorage = $this->createMock(TokenStorage::class);
        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $source = new DraftableEntityStub();
        $filter = new OwnerFilter($tokenStorage);
        $filter->apply($source, null, null);
        $this->assertInstanceOf(User::class, $source->getDraftOwner());
    }
}
