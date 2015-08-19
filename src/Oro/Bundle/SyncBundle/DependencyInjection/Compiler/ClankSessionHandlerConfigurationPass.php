<?php

namespace Oro\Bundle\SyncBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ClankSessionHandlerConfigurationPass implements CompilerPassInterface
{
    const SESSION_HANDLER_CLASS_SUFFIX = 'SessionHandler';
    const SESSION_HANDLER_PARAM = 'jdare_clank.session_handler';
    const APP_SESSION_HANDLER_SERVICE = 'session.handler';
    const PDO_SESSION_HANDLER_SERVICE = 'session.handler.pdo';
    const PDO_SESSION_HANDLER_TYPE = 'pdo';
    const PERIODIC_SERVICES_PARAM = 'jdare_clank.periodic_services';
    const PING_SERVICE_PREFIX = 'oro_wamp.ping.';
    const PING_INTERVAL_PARAM = 'oro_wamp.ping.interval';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasParameter(self::SESSION_HANDLER_PARAM)) {
            // Clank service is not used
            return;
        }
        $sessionHandlerId = $container->getParameter(self::SESSION_HANDLER_PARAM);
        if (!$sessionHandlerId
            && $this->hasService($container, self::PDO_SESSION_HANDLER_SERVICE)
            && $this->hasService($container, self::APP_SESSION_HANDLER_SERVICE)
            && $this->isDefaultSessionHandlerAllowed($container)
        ) {
            // by default enable a session sharing if an application session storage is a database
            // and a session sharing is not disabled in application configs
            $appSessionHandlerType = $this->getSessionHandlerType($container, self::APP_SESSION_HANDLER_SERVICE);
            if ($appSessionHandlerType === self::PDO_SESSION_HANDLER_TYPE) {
                $sessionHandlerId = self::PDO_SESSION_HANDLER_SERVICE;
                $container->setParameter(self::SESSION_HANDLER_PARAM, $sessionHandlerId);
            }
        }
        if (!$sessionHandlerId || !$this->hasService($container, $sessionHandlerId)) {
            // Clank service does not have a session handler or a configured handler does not exist
            return;
        }

        $this->configurePingService($container, $sessionHandlerId);
    }

    /**
     * Adds a periodic service to check that WebSocket server has a connection to a session storage
     * A ping service should be registered in DIC using the following naming convention:
     * "oro_wamp.ping.{session_handler_type}"
     * for example "oro_wamp.ping.pdo"
     * If a ping service for current session handler type does not exist this method does nothing.
     *
     * @param ContainerBuilder $container
     * @param string           $sessionHandlerId
     */
    protected function configurePingService(ContainerBuilder $container, $sessionHandlerId)
    {
        $pingInterval = $container->getParameter(self::PING_INTERVAL_PARAM);
        if (!$pingInterval) {
            // A ping service is disabled
            return;
        }
        $pingServiceId = self::PING_SERVICE_PREFIX . $this->getSessionHandlerType($container, $sessionHandlerId);
        if (!$this->hasService($container, $pingServiceId)) {
            // A ping service is not needed for current session handler type
            return;
        }


        // make sure that a ping service exists in a list of periodic services
        if ($container->hasParameter(self::PERIODIC_SERVICES_PARAM)) {
            $periodicServices = $container->getParameter(self::PERIODIC_SERVICES_PARAM);
            if (!$periodicServices || !$this->hasPeriodicService($periodicServices, $pingServiceId)) {
                $periodicServices[] = [
                    'service' => $pingServiceId,
                    'time'    => $pingInterval
                ];
            }
            $container->setParameter(self::PERIODIC_SERVICES_PARAM, $periodicServices);
        }
    }

    /**
     * Checks whether a session handler is set in application configs
     * If so, setting of default handler should not be allowed
     *
     * @param ContainerBuilder $container
     *
     * @return bool
     */
    protected function isDefaultSessionHandlerAllowed(ContainerBuilder $container)
    {
        // check if a Clank session handler is configured (including setting to null) in application configs
        $configs = $container->getExtensionConfig('clank');
        foreach ($configs as $config) {
            if (array_key_exists('session_handler', $config)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $sessionHandlerId
     *
     * @return string
     */
    protected function getSessionHandlerType(ContainerBuilder $container, $sessionHandlerId)
    {
        $sessionHandlerClass = $container->findDefinition($sessionHandlerId)->getClass();

        $type         = $this->getShortClassName($sessionHandlerClass);
        $suffixLength = strlen(self::SESSION_HANDLER_CLASS_SUFFIX);
        if (substr($type, -$suffixLength) === self::SESSION_HANDLER_CLASS_SUFFIX) {
            $type = substr($type, 0, strlen($type) - $suffixLength);
        }
        $type = strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_$1', $type));

        return $type;
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $serviceId
     *
     * @return bool
     */
    protected function hasService(ContainerBuilder $container, $serviceId)
    {
        return $container->hasDefinition($serviceId) || $container->hasAlias($serviceId);
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

    /**
     * Gets a class name without a namespace
     *
     * @param string $className
     *
     * @return string
     */
    protected function getShortClassName($className)
    {
        $lastDelimiter = strrpos($className, '\\');

        return false === $lastDelimiter
            ? $className
            : substr($className, $lastDelimiter + 1);
    }
}
