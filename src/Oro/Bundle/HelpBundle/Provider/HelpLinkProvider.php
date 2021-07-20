<?php

namespace Oro\Bundle\HelpBundle\Provider;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\HelpBundle\Annotation\Help;
use Oro\Bundle\HelpBundle\Configuration\ConfigurationProvider;
use Oro\Bundle\PlatformBundle\Composer\VersionHelper;
use Oro\Bundle\UIBundle\Provider\ControllerClassProvider;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerNameParser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The help link URL provider.
 */
class HelpLinkProvider
{
    private const FORMAT          = '%server%/%vendor%/%bundle%/%controller%_%action%';
    private const GROUP_SEPARATOR = '/';

    /** @var array */
    private $defaultConfig;

    /** @var ConfigurationProvider */
    private $configProvider;

    /** @var RequestStack */
    private $requestStack;

    /** @var ControllerClassProvider */
    private $controllerClassProvider;

    /** @var ControllerNameParser */
    private $parser;

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

    /** @var CacheProvider */
    private $cache;

    public function __construct(
        array $defaultConfig,
        ConfigurationProvider $configProvider,
        RequestStack $requestStack,
        ControllerClassProvider $controllerClassProvider,
        ControllerNameParser $parser,
        VersionHelper $helper,
        CacheProvider $cache
    ) {
        $this->defaultConfig = $defaultConfig;
        $this->configProvider = $configProvider;
        $this->requestStack = $requestStack;
        $this->controllerClassProvider = $controllerClassProvider;
        $this->parser = $parser;
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

        $helpLink = false;
        if ($this->requestRoute) {
            $helpLink = $this->cache->fetch($this->requestRoute);
        }
        if (false === $helpLink) {
            $helpLink = $this->constructedHelpLinkUrl();

            if ($this->requestRoute) {
                $this->cache->save($this->requestRoute, $helpLink);
            }
        }

        return $helpLink;
    }

    /**
     * Makes sure that request depended properties are set.
     */
    private function ensureRequestSet()
    {
        if (null === $this->request) {
            $request = $this->requestStack->getMasterRequest();
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

        $keys = ['server', 'vendor', 'bundle', 'controller', 'action', 'uri'];
        $replaceParams = [];
        foreach ($keys as $key) {
            $replaceParams['%' . $key . '%'] = $config[$key] ?? '';
        }

        if (isset($config['uri'])) {
            $link = strtr('%server%/%uri%', $replaceParams);
        } elseif (isset($config['vendor'], $config['bundle'], $config['controller'], $config['action'])) {
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

        return $this->appendVersion(preg_replace('/(^:)\/+/', '/', $link));
    }

    /**
     * Append Platform version to URL
     *
     * @param string $url
     * @return string
     */
    private function appendVersion($url)
    {
        $delimiter = false === strpos($url, '?') ? '?' : '&';

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
        $bundle = $controllerData['bundle'];
        $controller = $controllerData['controller'];
        $action = $controllerData['action'];

        $configData[] = [
            'id' => $vendor,
            'section' => 'vendors',
            'key' => 'vendor'
        ];
        $configData[] = [
            'id' => $bundle,
            'section' => 'resources',
            'key' => 'bundle'
        ];
        $configData[] = [
            'id' => $bundle . ':' . $controller,
            'section' => 'resources',
            'key' => 'controller'
        ];
        $configData[] = [
            'id' => sprintf('%s:%s:%s', $bundle, $controller, $action),
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
     * Parses request controller and returns vendor, bundle, controller, action
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

        $controllerActionKey = $this->parser->build($controller);
        $controllerNameParts = explode('::', $controller);
        $vendorName = current(explode('\\', $controllerNameParts[0]));
        list($bundleName, $controllerName, $actionName) = explode(':', $controllerActionKey);

        return $this->parserCache[$controller] = [
            'vendor' => $vendorName,
            'bundle' => $bundleName,
            'controller' => $controllerName,
            'action' => $actionName,
        ];
    }
}
