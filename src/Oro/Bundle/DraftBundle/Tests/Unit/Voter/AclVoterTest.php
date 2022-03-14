<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Voter;

use Oro\Bundle\DraftBundle\Tests\Unit\Stub\DraftableEntityStub;
use Oro\Bundle\DraftBundle\Voter\AclVoter;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoterInterface;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class AclVoterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider voteDataProvider
     */
    public function testVote(array $expected, string $sourceUuid = null): void
    {
        $source = new DraftableEntityStub();
        $source->setDraftUuid($sourceUuid);

        $token = $this->createMock(TokenInterface::class);
        $result = VoterInterface::ACCESS_ABSTAIN;

        $innerVoter = $this->createMock(AclVoterInterface::class);
        $innerVoter->expects($this->once())
            ->method('vote')
            ->with(self::identicalTo($token), self::identicalTo($source), $expected)
            ->willReturn($result);

        $voter = new AclVoter($innerVoter);
        $this->assertSame($result, $voter->vote($token, $source, ['MASTER', 'VIEW', 'EDIT', 'DELETE']));
    }

    public function voteDataProvider(): array
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
