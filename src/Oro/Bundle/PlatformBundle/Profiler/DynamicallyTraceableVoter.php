<?php

namespace Oro\Bundle\PlatformBundle\Profiler;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\TraceableVoter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Event\VoteEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Disables vote event dispatch when the security data collector is disabled
 */
class DynamicallyTraceableVoter extends TraceableVoter
{
    private EventDispatcherInterface $eventDispatcher;

    private VoterInterface $voter;

    public function __construct(VoterInterface $voter, EventDispatcherInterface $eventDispatcher)
    {
        parent::__construct($voter, $eventDispatcher);
        $this->voter = $voter;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function vote(TokenInterface $token, $subject, array $attributes): int
    {
        $result = $this->voter->vote($token, $subject, $attributes);
        if (ProfilerConfig::isCollectorEnabled('security')) {
            $this->eventDispatcher->dispatch(
                new VoteEvent($this->voter, $subject, $attributes, $result),
                'debug.security.authorization.vote'
            );
        }

        return $result;
    }
}
