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

    /**
     * @param ConfigManager $configManager
     * @param VoteListener $innerListener
     */
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
        if ($this->configManager->get('oro_security.symfony_profiler_collection_of_voter_decisions', true)) {
            $this->innerListener->onVoterVote($event);
        }
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return parent::getSubscribedEvents();
    }
}
