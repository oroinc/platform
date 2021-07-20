<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Voter;

use Oro\Bundle\DraftBundle\Tests\Unit\Stub\DraftableEntityStub;
use Oro\Bundle\DraftBundle\Voter\AclVoter;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter as BaseAclVoter;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class AclVoterTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @dataProvider permissionProvider
     */
    public function testVote(array $expected, string $sourceUuid = null): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|BaseAclVoter $decorateVoter */
        $decorateVoter = $this->createMock(BaseAclVoter::class);
        /** @var \PHPUnit\Framework\MockObject\MockObject|TokenInterface $token */
        $token = $this->createMock(TokenInterface::class);
        $source = $this->getEntity(DraftableEntityStub::class, ['draftUuid' => $sourceUuid]);
        $voter = new AclVoter($decorateVoter);
        $attributes = ['MASTER', 'VIEW', 'EDIT', 'DELETE'];

        $decorateVoter
            ->expects($this->once())
            ->method('vote')
            ->with($token, $source, $expected)
            ->willReturn(VoterInterface::ACCESS_ABSTAIN);

        $voter->vote(
            $token,
            $source,
            $attributes
        );
    }

    public function permissionProvider(): array
    {
        return [
            'Draft with view permission' => [
                'expected' => ['MASTER'],
                'sourceUuid' => UUIDGenerator::v4(),
            ],
            'Real entity with view permission' => [
                'expected' => ['MASTER', 'VIEW', 'EDIT', 'DELETE'],
                'sourceUuid' => '',
            ],
        ];
    }
}
