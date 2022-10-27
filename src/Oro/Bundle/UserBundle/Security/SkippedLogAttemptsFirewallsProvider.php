<?php

namespace Oro\Bundle\UserBundle\Security;

/**
 * Returns the list of security firewalls for which the login attempts should not be logged.
 */
class SkippedLogAttemptsFirewallsProvider
{
    private array $skippedFirewalls = [];

    public function addSkippedFirewall($firewall): void
    {
        $this->skippedFirewalls[] = $firewall;
    }

    public function getSkippedFirewalls(): array
    {
        return $this->skippedFirewalls;
    }
}
