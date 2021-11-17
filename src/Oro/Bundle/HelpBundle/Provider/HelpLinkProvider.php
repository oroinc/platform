<?php

namespace Oro\Bundle\HelpBundle\Provider;

use Oro\Bundle\HelpBundle\Annotation\Help;
use Oro\Bundle\HelpBundle\Configuration\ConfigurationProvider;
use Oro\Bundle\PlatformBundle\Composer\VersionHelper;
use Oro\Bundle\UIBundle\Provider\ControllerClassProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * The help link URL provider.
 */
class HelpLinkProvider
{
    private const FORMAT          = '%server%/%vendor%/%controller%_%action%';
    private const GROUP_SEPARATOR = '/';

    /** @var array */
    private $defaultConfig;

    /** @var ConfigurationProvider */
    private $configProvider;

    /** @var RequestStack */
    private $requestStack;

    /** @var ControllerClassProvider */
    private $controllerClassProvider;

    /** @var VersionHelper */
    private $helper;

    /** @var array */
    private $parserCache = [];

    /** @var string */
    private $requestRoute;

    /** @var Request */
    private $request;

    /** @var Help[]|null */
    private $helpAnnotation;

    /** @var CacheInterface */
    private $cache;

    public function __construct(
        array $defaultConfig,
        ConfigurationProvider $configProvider,
        RequestStack $requestStack,
        ControllerClassProvider $controllerClassProvider,
        VersionHelper $helper,
        CacheInterface $cache
    ) {
        $this->defaultConfig = $defaultConfig;
        $this->configProvider = $configProvider;
        $this->requestStack = $requestStack;
        $this->controllerClassProvider = $controllerClassProvider;
        $this->helper = $helper;
        $this->cache = $cache;
    }

    /**
     * Get help link URL.
     *
     * @return string
     */
    public function getHelpLinkUrl()
    {
        $this->ensureRequestSet();

        return $this->requestRoute
            ? $this->cache->get($this->requestRoute, function () {
                return $this->constructedHelpLinkUrl();
            }) : $this->constructedHelpLinkUrl();
    }

    /**
     * Makes sure that request depended properties are set.
     */
    private function ensureRequestSet()
    {
        if (null === $this->request) {
            $request = $this->requestStack->getMainRequest();
            if (null !== $request) {
                $this->requestRoute = $request->get('_route');
                $this->helpAnnotation = null;
                $this->request = $request;
            }
        }
    }

    /**
     * Construct help link URL based on route.
     *
     * @return string
     */
    private function constructedHelpLinkUrl()
    {
        $config = $this->getConfiguration();
        if (isset($config['link'])) {
            return $config['link'];
        }

        $config['server'] = rtrim($config['server'], '/');
        if (isset($config['prefix'], $config['vendor'])) {
            $config['vendor'] = $config['prefix'] . self::GROUP_SEPARATOR . $config['vendor'];
        }

        $keys = ['server', 'vendor', 'controller', 'action', 'uri'];
        $replaceParams = [];
        foreach ($keys as $key) {
            $replaceParams['%' . $key . '%'] = $config[$key] ?? '';
        }

        if (isset($config['uri'])) {
            $link = strtr('%server%/%uri%', $replaceParams);
        } elseif (isset($config['vendor'], $config['controller'], $config['action'])) {
            $link = strtr(self::FORMAT, $replaceParams);
        } else {
            $link = $config['server'];
        }

        $request = $this->request;
        $link = preg_replace_callback(
            '/{(\w+)}/',
            function ($matches) use ($request) {
                if (count($matches) > 1) {
                    return $request->get($matches[1]);
                }

                return '';
            },
            $link
        );

        return $this->appendVersion(preg_replace('/(^:)\/+|\\\/', '/', $link));
    }

    /**
     * Append Platform version to URL
     *
     * @param string $url
     * @return string
     */
    private function appendVersion($url)
    {
        $delimiter = !str_contains($url, '?') ? '?' : '&';

        return $url . $delimiter . 'v=' . $this->helper->getVersion();
    }

    /**
     * Get merged flat configuration for requested controller.
     *
     * @return array
     */
    private function getConfiguration()
    {
        $result = $this->defaultConfig;

        $controllerData = $this->getRequestControllerData();
        if ($controllerData) {
            $this->mergeRequestControllerConfig($result, $controllerData);
        }
        $this->mergeAnnotationConfig($result);
        $this->mergeRoutesConfig($result);
        if ($controllerData) {
            $this->mergeVendorsAndResourcesConfig($result, $controllerData);
        }

        return $result;
    }

    /**
     * Apply configuration from annotations
     */
    private function mergeAnnotationConfig(array &$resultConfig)
    {
        if (null === $this->helpAnnotation && null !== $this->request) {
            $helpAnnotation = $this->request->get('_' . Help::ALIAS);
            if (!$helpAnnotation) {
                $helpAnnotation = [];
            } elseif (!is_array($helpAnnotation)) {
                $helpAnnotation = [$helpAnnotation];
            }
            $this->helpAnnotation = $helpAnnotation;
        }
        if (!$this->helpAnnotation) {
            return;
        }

        foreach ($this->helpAnnotation as $help) {
            if ($help instanceof Help) {
                $resultConfig = array_merge($resultConfig, $help->getConfigurationArray());
            }
        }
    }

    /**
     * Apply configuration from "routes" section of configuration
     */
    private function mergeRoutesConfig(array &$resultConfig)
    {
        if (!$this->requestRoute) {
            return;
        }

        $config = $this->configProvider->getConfiguration();
        if (isset($config['routes'][$this->requestRoute])) {
            $resultConfig = array_merge($resultConfig, $config['routes'][$this->requestRoute]);
        }
    }

    /**
     * Apply configuration from request controller name
     */
    private function mergeRequestControllerConfig(array &$resultConfig, array $controllerData)
    {
        $resultConfig = array_merge($resultConfig, $controllerData);
    }

    /**
     * Apply configuration from "vendors" and "resources" section of configuration
     */
    private function mergeVendorsAndResourcesConfig(array &$resultConfig, array $controllerData)
    {
        $vendor = $controllerData['vendor'];
        $controller = $controllerData['controller'];
        $action = $controllerData['action'];

        $configData[] = [
            'id' => $vendor,
            'section' => 'vendors',
            'key' => 'vendor'
        ];
        $configData[] = [
            'id' => $controller,
            'section' => 'resources',
            'key' => 'controller'
        ];
        $configData[] = [
            'id' => sprintf('%s::%s', $controller, $action),
            'section' => 'resources',
            'key' => 'action'
        ];

        $config = $this->configProvider->getConfiguration();
        foreach ($configData as $searchData) {
            $id = $searchData['id'];
            $section = $searchData['section'];
            if (isset($config[$section][$id])) {
                $rawConfiguration = $config[$section][$id];
                if (isset($rawConfiguration['alias'])) {
                    $rawConfiguration[$searchData['key']] = $rawConfiguration['alias'];
                    unset($rawConfiguration['alias']);
                }
                $resultConfig = array_merge($resultConfig, $rawConfiguration);
            }
        }
    }

    /**
     * @return array
     */
    private function getRequestControllerData()
    {
        $requestController = null;
        if ($this->requestRoute) {
            $controllers = $this->controllerClassProvider->getControllers();
            if (isset($controllers[$this->requestRoute])) {
                $controller = $controllers[$this->requestRoute];
                $requestController = sprintf('%s::%s', $controller[0], $controller[1]);
            }
        }

        return $this->parseRequestController($requestController);
    }

    /**
     * Parses request controller and returns vendor, controller, action
     *
     * @param string $controller
     * @return array
     */
    private function parseRequestController($controller)
    {
        if (!is_string($controller)) {
            return [];
        }

        if (array_key_exists($controller, $this->parserCache)) {
            return $this->parserCache[$controller];
        }

        $controllerNameParts = explode('::', $controller);
        $controllerName = $controllerNameParts[0];
        $vendorName = current(explode('\\', $controllerName));

        return $this->parserCache[$controller] = [
            'vendor' => $vendorName,
            'controller' => $controllerName,
            'action' => $controllerNameParts[1],
        ];
    }
}
