<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Unit\Acl\Voter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\DigitalAssetBundle\Acl\Voter\DigitalAssetDeleteVoter;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\DigitalAssetBundle\Tests\Unit\Stub\DigitalAssetStub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class DigitalAssetDeleteVoterTest extends TestCase
{
    /** @var TokenInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $token;

    /** @var DigitalAssetDeleteVoter */
    private $voter;

    protected function setUp(): void
    {
        $this->token = $this->createMock(TokenInterface::class);

        $this->voter = new DigitalAssetDeleteVoter();
    }

    public function testVoteWithUnsupportedObject(): void
    {
        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, new \stdClass(), [])
        );
    }

    public function testVoteWithUnsupportedAttribute(): void
    {
        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, new DigitalAsset(), ['ATTR'])
        );
    }

    public function testVoteAbstain(): void
    {
        $subject = new DigitalAssetStub();
        $subject->childFiles = new ArrayCollection([]);
        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, $subject, ['DELETE'])
        );
    }

    public function testVoteDenied(): void
    {
        $subject = new DigitalAssetStub();
        $subject->childFiles = new ArrayCollection(['existing_child']);
        $this->assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->token, $subject, ['DELETE'])
        );
    }
}
