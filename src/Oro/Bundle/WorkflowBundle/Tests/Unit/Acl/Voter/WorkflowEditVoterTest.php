<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Acl\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\WorkflowBundle\Acl\Voter\WorkflowEditVoter;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class WorkflowEditVoterTest extends \PHPUnit_Framework_TestCase
{
    /** @var TokenInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $token;

    /** @var WorkflowEditVoter */
    protected $voter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->token = $this->createMock(TokenInterface::class);

        $this->voter = new WorkflowEditVoter();
    }

    public function testVoteWithUnsupportedAttribute()
    {
        $this->assertEquals(
            WorkflowEditVoter::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, null, ['ATTR'])
        );
    }

    public function testVoteWithUnsupportedSubject()
    {
        $this->assertEquals(
            WorkflowEditVoter::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, null, ['EDIT'])
        );
    }

    public function testVoteWithActiveWorkflow()
    {
        $definition = new WorkflowDefinition();
        $definition->setActive(true);

        $this->assertEquals(
            WorkflowEditVoter::ACCESS_DENIED,
            $this->voter->vote($this->token, $definition, ['EDIT'])
        );
    }

    public function testVoteWithInactiveWorkflow()
    {
        $definition = new WorkflowDefinition();
        $definition->setActive(false);

        $this->assertEquals(
            WorkflowEditVoter::ACCESS_GRANTED,
            $this->voter->vote($this->token, $definition, ['EDIT'])
        );
    }
}
