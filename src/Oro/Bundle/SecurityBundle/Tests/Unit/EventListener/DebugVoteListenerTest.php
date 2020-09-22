<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\EventListener\DebugVoteListener;
use Symfony\Bundle\SecurityBundle\EventListener\VoteListener;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Event\VoteEvent;

class DebugVoteListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var VoteListener|\PHPUnit\Framework\MockObject\MockObject */
    private $innerListener;

    /** @var DebugVoteListener */
    private $listener;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->innerListener = $this->createMock(VoteListener::class);

        $this->listener = new DebugVoteListener($this->configManager, $this->innerListener);
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertEquals(
            ['debug.security.authorization.vote' => 'onVoterVote'],
            $this->listener->getSubscribedEvents()
        );
    }

    public function testOnVoterVoteEnabled(): void
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_security.symfony_profiler_collection_of_voter_decisions', true)
            ->willReturn(true);

        $event = new VoteEvent($this->createMock(VoterInterface::class), new \stdClass, [], 0);

        $this->innerListener->expects($this->exactly(2))
            ->method('onVoterVote')
            ->with($event);

        $this->listener->onVoterVote($event);

        // Checks local cache.
        $this->listener->onVoterVote($event);
    }

    public function testOnVoterVoteDisabled(): void
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_security.symfony_profiler_collection_of_voter_decisions', true)
            ->willReturn(false);

        $this->innerListener->expects($this->never())
            ->method('onVoterVote');

        $this->listener->onVoterVote(new VoteEvent($this->createMock(VoterInterface::class), new \stdClass, [], 0));

        // Checks local cache.
        $this->listener->onVoterVote(new VoteEvent($this->createMock(VoterInterface::class), new \stdClass, [], 0));
    }
}
