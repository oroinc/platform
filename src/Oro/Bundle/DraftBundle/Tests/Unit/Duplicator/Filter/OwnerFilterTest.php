<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Duplicator\Filter;

use Oro\Bundle\DraftBundle\Duplicator\Filter\OwnerFilter;
use Oro\Bundle\DraftBundle\Tests\Unit\Stub\DraftableEntityStub;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class OwnerFilterTest extends TestCase
{
    public function testApply(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn(new User());

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $source = new DraftableEntityStub();
        $filter = new OwnerFilter($tokenStorage);
        $filter->apply($source, null, null);
        $this->assertInstanceOf(User::class, $source->getDraftOwner());
    }
}
