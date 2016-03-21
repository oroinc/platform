<?php

namespace Oro\Bundle\ActionBundle\Configuration;

use Doctrine\Common\Collections\Collection;

use Psr\Log\LoggerInterface;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class OperationConfigurationValidator implements ConfigurationValidatorInterface
{
    /** @var RouterInterface */
    protected $router;

    /** @var \Twig_ExistsLoaderInterface */
    protected $twigLoader;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var LoggerInterface */
    protected $logger;

    /** @var bool */
    protected $debug;

    /** @var Collection */
    protected $errors;

    /**
     * @param RouterInterface $router
     * @param \Twig_Loader_Filesystem $twigLoader
     * @param DoctrineHelper $doctrineHelper
     * @param LoggerInterface $logger
     * @param bool $debug
     */
    public function __construct(
        RouterInterface $router,
        \Twig_Loader_Filesystem $twigLoader,
        DoctrineHelper $doctrineHelper,
        LoggerInterface $logger,
        $debug
    ) {
        $this->router = $router;
        $this->twigLoader = $twigLoader;
        $this->doctrineHelper = $doctrineHelper;
        $this->logger = $logger;
        $this->debug = $debug;
    }

    /**
     * @param array $configuration
     * @param Collection $errors
     */
    public function validate(array $configuration, Collection $errors = null)
    {
        $this->errors = $errors;

        foreach ($configuration as $name => $action) {
            $this->validateTemplate($action, $name, 'button_options');
            $this->validateTemplate($action, $name, 'frontend_options');
            $this->validateFormOptions($action, $name);
            $this->validateRoutes($action['routes'], $this->getPath($name, 'routes'));
            $this->validateEntities($action['entities'], $this->getPath($name, 'entities'));
        }
    }

    /**
     * @param array $config
     * @param string $path
     * @param string $sectionName
     */
    protected function validateTemplate(array $config, $path, $sectionName)
    {
        if (!array_key_exists($sectionName, $config)) {
            return;
        }

        $optionsPath = $this->getPath($path, $sectionName);
        $options = $config[$sectionName];

        $this->assertTemplate($options, $optionsPath, 'template');
    }

    /**
     * @param array $options
     * @param string $path
     * @param string $paramName
     */
    protected function assertTemplate(array $options, $path, $paramName)
    {
        if (isset($options[$paramName]) && !$this->twigLoader->exists($options[$paramName])) {
            $this->handleError(
                $this->getPath($path, $paramName),
                'Unable to find template "%s"',
                $options[$paramName],
                false
            );
        }
    }

    /**
     * @param array $config
     * @param string $path
     */
    protected function validateFormOptions(array $config, $path)
    {
        $sectionName = 'form_options';
        if (!array_key_exists($sectionName, $config)) {
            return;
        }

        $optionsPath = $this->getPath($path, $sectionName);

        $this->validateFormOptionsAttributes($config, 'attribute_fields', $optionsPath);
        $this->validateFormOptionsAttributes($config, 'attribute_default_values', $optionsPath);
    }

    /**
     * @param array $config
     * @param string $sectionName
     * @param string $path
     */
    protected function validateFormOptionsAttributes(array $config, $sectionName, $path)
    {
        if (!array_key_exists($sectionName, $config['form_options'])) {
            return;
        }

        foreach (array_keys($config['form_options'][$sectionName]) as $attributeName) {
            if (!isset($config['attributes'][$attributeName])) {
                $this->handleError(
                    $this->getPath($path, $sectionName),
                    'Unknown attribute "%s".',
                    $attributeName,
                    false
                );
            }
        }
    }

    /**
     * @param array $items
     * @param string $path
     */
    protected function validateRoutes(array $items, $path)
    {
        if (!$items) {
            return;
        }

        $routeCollection = $this->router->getRouteCollection();

        foreach ($items as $key => $item) {
            if (!$routeCollection->get($item)) {
                $this->handleError($this->getPath($path, $key), 'Route "%s" not found.', $item);
            }
        }
    }

    /**
     * @param array $items
     * @param string $path
     */
    protected function validateEntities(array $items, $path)
    {
        foreach ($items as $key => $item) {
            if (!$this->validateEntity($item)) {
                $this->handleError($this->getPath($path, $key), 'Entity "%s" not found.', $item);
            }
        }
    }

    /**
     * @param string $entityName
     * @return boolean
     */
    protected function validateEntity($entityName)
    {
        try {
            $entityClass = $this->doctrineHelper->getEntityClass($entityName);

            if (!class_exists($entityClass, true)) {
                return false;
            }

            $reflection = new \ReflectionClass($entityClass);

            return $this->doctrineHelper->isManageableEntity($reflection->getName());
        } catch (\Exception $e) {
        }

        return false;
    }

    /**
     * @param string $path
     * @param string $subpath
     * @return string
     */
    protected function getPath($path, $subpath)
    {
        return $path . '.' . $subpath;
    }

    /**
     * @param string $path
     * @param string $message
     * @param mixed $value
     * @param bool $silent
     * @throws InvalidConfigurationException
     */
    protected function handleError($path, $message, $value, $silent = true)
    {
        $errorMessage = sprintf('%s: ' . $message, $path, $value);
        if ($this->debug) {
            $this->logger->warning('InvalidConfiguration: ' . $errorMessage, ['ActionConfiguration']);
        }

        if (!$silent) {
            throw new InvalidConfigurationException($errorMessage);
        }

        if ($this->errors !== null) {
            $this->errors->add($errorMessage);
        }
    }
}
