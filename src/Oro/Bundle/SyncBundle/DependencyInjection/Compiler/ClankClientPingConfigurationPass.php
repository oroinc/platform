<?php

namespace Oro\Bundle\SyncBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ClankClientPingConfigurationPass implements CompilerPassInterface
{
    const PERIODIC_SERVICES_PARAM = 'jdare_clank.periodic_services';
    const PING_SERVICE            = 'oro_wamp.client.ping';
    const PING_INTERVAL_PARAM     = 'oro_wamp.client_ping.interval';

    /**
     * Adds a periodic service to broadcast clients to avoid connection loose
     *
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $pingInterval = $container->getParameter(self::PING_INTERVAL_PARAM);
        if (!$pingInterval) {
            // Client pinging is disabled
            return;
        }

        // make sure that a ping service exists in a list of periodic services
        if ($container->hasParameter(self::PERIODIC_SERVICES_PARAM)) {
            $periodicServices = $container->getParameter(self::PERIODIC_SERVICES_PARAM);
            if (!$periodicServices || !$this->hasPeriodicService($periodicServices, self::PING_SERVICE)) {
                $periodicServices[] = [
                    'service' => self::PING_SERVICE,
                    'time'    => $pingInterval
                ];
            }
            $container->setParameter(self::PERIODIC_SERVICES_PARAM, $periodicServices);
        }
    }

    /**
     * @param array  $periodicServices
     * @param string $serviceId
     *
     * @return bool
     */
    protected function hasPeriodicService(array $periodicServices, $serviceId)
    {
        foreach ($periodicServices as $periodicService) {
            if (isset($periodicService['service']) && $periodicService['service'] === $serviceId) {
                return true;
            }
        }

        return false;
    }
}
