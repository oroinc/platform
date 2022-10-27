<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain;

use Oro\Bundle\SecurityBundle\Acl\Domain\CacheableSecurityIdentityRetrievalStrategy;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Model\SecurityIdentityRetrievalStrategyInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class CacheableSecurityIdentityRetrievalStrategyTest extends \PHPUnit\Framework\TestCase
{
    /** @var SecurityIdentityRetrievalStrategyInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerStrategy;

    /** @var CacheableSecurityIdentityRetrievalStrategy */
    private $strategy;

    protected function setUp(): void
    {
        $this->innerStrategy = $this->createMock(SecurityIdentityRetrievalStrategyInterface::class);

        $this->strategy = new CacheableSecurityIdentityRetrievalStrategy($this->innerStrategy);
    }

    public function testGetSecurityIdentities()
    {
        $token1 = $this->createMock(TokenInterface::class);
        $token1->expects(self::any())
            ->method('getUsername')
            ->willReturn('user1');

        $token2 = $this->createMock(TokenInterface::class);
        $token2->expects(self::any())
            ->method('getUsername')
            ->willReturn('user2');

        $sids1 = [new RoleSecurityIdentity('ROLE1')];
        $sids2 = [new RoleSecurityIdentity('ROLE2')];

        $this->innerStrategy->expects(self::exactly(2))
            ->method('getSecurityIdentities')
            ->willReturnMap([
                [$token1, $sids1],
                [$token2, $sids2]
            ]);

        self::assertEquals($sids1, $this->strategy->getSecurityIdentities($token1));
        self::assertEquals($sids2, $this->strategy->getSecurityIdentities($token2));

        // test the cache
        self::assertEquals($sids1, $this->strategy->getSecurityIdentities($token1));
        self::assertEquals($sids2, $this->strategy->getSecurityIdentities($token2));
    }
}
