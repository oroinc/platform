<?php

namespace Oro\Bundle\ApiBundle\Security;

use Oro\Bundle\ApiBundle\Security\Http\Firewall\FeatureAccessListener;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SecurityBundle\Authentication\Listener\OnNoTokenAccessListener;
use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
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
    /** @var array [firewall name => ['feature_name' => name, 'feature_firewall_authenticators' => [class, ...]], ...] */
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
    public function getListeners(Request $request): array
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
                $this->featureDependedFirewalls[$firewallName]['feature_firewall_authenticators']
            );
        }

        return [$listeners, $exceptionListener, $logoutListener];
    }

    public function getFirewallConfig(Request $request): ?FirewallConfig
    {
        $context = $this->getContext($request);
        if (null === $context) {
            return null;
        }

        return $context->getConfig();
    }

    private function getContext(Request $request): ?FirewallContext
    {
        $method = new \ReflectionMethod($this, 'getFirewallContext');
        $method->setAccessible(true);

        $context = $method->invoke($this, $request);
        // removing the stateless attribute for a csrf-protected api requests that should be stateful
        if (null !== $context
            && $context->getConfig()?->isStateless()
            && $request->attributes->has('_stateless')
            && $request->headers->has(CsrfRequestManager::CSRF_HEADER)) {
            $request->attributes->remove('_stateless');
        }

        return $context;
    }

    private function getApplicableListeners(iterable $listeners, array $excludedAuthenticatorClasses): iterable
    {
        if (\count($listeners) > 0) {
            $applicableListeners = [];
            $isFeatureListenerAdded = false;
            foreach ($listeners as $listener) {
                if ((!$isFeatureListenerAdded && $listener instanceof AccessListener)
                    || $listener instanceof OnNoTokenAccessListener) {
                    $applicableListeners[] = $this->featureAccessListener;
                    $isFeatureListenerAdded = true;
                }
                $applicableListeners[] = $listener;
            }

            return $applicableListeners;
        }

        if (\count($excludedAuthenticatorClasses) === 0) {
            return [$this->featureAccessListener];
        }

        return $listeners;
    }
}
