<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\WorkflowBundle\Acl\Voter\WorkflowEditVoter;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class WorkflowEditVoterTest extends TestCase
{
    private TokenInterface&MockObject $token;
    private WorkflowEditVoter $voter;

    #[\Override]
    protected function setUp(): void
    {
        $this->token = $this->createMock(TokenInterface::class);

        $this->voter = new WorkflowEditVoter();
    }

    public function testVoteWithUnsupportedAttribute(): void
    {
        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, null, ['ATTR'])
        );
    }

    public function testVoteWithUnsupportedSubject(): void
    {
        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, null, ['EDIT'])
        );
    }

    public function testVoteWithActiveWorkflow(): void
    {
        $definition = new WorkflowDefinition();
        $definition->setActive(true);

        $this->assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->token, $definition, ['EDIT'])
        );
    }

    public function testVoteWithInactiveWorkflow(): void
    {
        $definition = new WorkflowDefinition();
        $definition->setActive(false);

        $this->assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->token, $definition, ['EDIT'])
        );
    }
}
