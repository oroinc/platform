<?php

namespace Oro\Bundle\ApiBundle\Security;

use Oro\Bundle\ApiBundle\Security\Http\Firewall\FeatureAccessListener;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\SecurityBundle\Security\FirewallContext;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Firewall\AccessListener;

/**
 * Overrides Symfony's firewall map to be able to remove authorization listeners that should not be used
 * for API feature depended firewalls when API feature is disabled.
 */
class FeatureDependedFirewallMap extends FirewallMap
{
    private FeatureChecker $featureChecker;
    private FeatureAccessListener $featureAccessListener;
    /** @var array [firewall name => ['feature_name' => name, 'feature_firewall_listeners' => [class, ...]], ...] */
    private array $featureDependedFirewalls;

    public function __construct(
        ContainerInterface $container,
        iterable $map,
        FeatureChecker $featureChecker,
        FeatureAccessListener $featureAccessListener,
        array $featureDependedFirewalls
    ) {
        parent::__construct($container, $map);
        $this->featureChecker = $featureChecker;
        $this->featureAccessListener = $featureAccessListener;
        $this->featureDependedFirewalls = $featureDependedFirewalls;
    }

    /**
     * {@inheritDoc}
     */
    public function getListeners(Request $request)
    {
        $context = $this->getContext($request);
        if (null === $context) {
            return [[], null, null];
        }

        $listeners = $context->getListeners();
        $exceptionListener = $context->getExceptionListener();
        $logoutListener = $context->getLogoutListener();

        $firewallName = $context->getConfig()->getName();
        if (isset($this->featureDependedFirewalls[$firewallName])
            && !$this->featureChecker->isFeatureEnabled($this->featureDependedFirewalls[$firewallName]['feature_name'])
        ) {
            $listeners = $this->getApplicableListeners(
                $listeners,
                $this->featureDependedFirewalls[$firewallName]['feature_firewall_listeners']
            );
        }

        return [$listeners, $exceptionListener, $logoutListener];
    }

    private function getContext(Request $request): ?FirewallContext
    {
        $method = new \ReflectionMethod($this, 'getFirewallContext');
        $method->setAccessible(true);

        return $method->invoke($this, $request);
    }

    /**
     * @param iterable $listeners
     * @param string[] $excludedListenerClasses
     *
     * @return iterable
     */
    private function getApplicableListeners(iterable $listeners, array $excludedListenerClasses): iterable
    {
        if (\count($listeners) > 0) {
            $applicableListeners = [];
            foreach ($listeners as $listener) {
                if ($this->isApplicableListener($listener, $excludedListenerClasses)) {
                    if ($listener instanceof AccessListener) {
                        $applicableListeners[] = $this->featureAccessListener;
                    }
                    $applicableListeners[] = $listener;
                }
            }

            return $applicableListeners;
        }

        if (\count($excludedListenerClasses) === 0) {
            return [$this->featureAccessListener];
        }

        return $listeners;
    }

    private function isApplicableListener(object $listener, array $excludedListenerClasses): bool
    {
        foreach ($excludedListenerClasses as $listenerClass) {
            if (is_a($listener, $listenerClass)) {
                return false;
            }
        }

        return true;
    }
}
