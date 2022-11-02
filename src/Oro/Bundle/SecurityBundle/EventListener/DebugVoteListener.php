<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symfony\Bundle\SecurityBundle\EventListener\VoteListener;
use Symfony\Component\Security\Core\Event\VoteEvent;

/**
 * Listen to vote events from traceable voters when
 */
class DebugVoteListener extends VoteListener
{
    /** @var ConfigManager */
    private $configManager;

    /** @var VoteListener */
    private $innerListener;

    /** @var bool|null */
    private $isEnabled;

    public function __construct(ConfigManager $configManager, VoteListener $innerListener)
    {
        $this->configManager = $configManager;
        $this->innerListener = $innerListener;
    }

    /**
     * Event dispatched by a voter during access manager decision.
     */
    public function onVoterVote(VoteEvent $event): void
    {
        if ($this->isEnabled()) {
            $this->innerListener->onVoterVote($event);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return parent::getSubscribedEvents();
    }

    private function isEnabled(): bool
    {
        if ($this->isEnabled === null) {
            $this->isEnabled = $this->configManager->get(
                'oro_security.symfony_profiler_collection_of_voter_decisions',
                true
            );
        }

        return $this->isEnabled;
    }
}
