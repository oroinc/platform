<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\WorkflowBundle\Acl\Voter\WorkflowEditVoter;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class WorkflowEditVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $token;

    /** @var WorkflowEditVoter */
    private $voter;

    protected function setUp(): void
    {
        $this->token = $this->createMock(TokenInterface::class);

        $this->voter = new WorkflowEditVoter();
    }

    public function testVoteWithUnsupportedAttribute()
    {
        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, null, ['ATTR'])
        );
    }

    public function testVoteWithUnsupportedSubject()
    {
        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, null, ['EDIT'])
        );
    }

    public function testVoteWithActiveWorkflow()
    {
        $definition = new WorkflowDefinition();
        $definition->setActive(true);

        $this->assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->token, $definition, ['EDIT'])
        );
    }

    public function testVoteWithInactiveWorkflow()
    {
        $definition = new WorkflowDefinition();
        $definition->setActive(false);

        $this->assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->token, $definition, ['EDIT'])
        );
    }
}
