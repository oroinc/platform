<?php

namespace Oro\Bundle\PlatformBundle\EventListener\Controller;

use Doctrine\Persistence\Proxy;
use Oro\Bundle\PlatformBundle\Interface\PHPAttributeConfigurationInterface;
use Oro\Component\PhpUtils\Attribute\Reader\AttributeReader;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * The ControllerListener modifies the Request object to apply configuration information
 * found in attributes implementing PHPAttributeConfigurationInterface
 *
 * Modified copy of
 * https://github.com/sensiolabs/SensioFrameworkExtraBundle/blob/v6.2.10/src/EventListener/ControllerListener.php
 *
 * Copyright (c) 2010-2020 Fabien Potencier
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 */
class ControllerListener implements EventSubscriberInterface
{
    public function __construct(private readonly AttributeReader $reader)
    {
    }

    public function onKernelController(KernelEvent $event): void
    {
        $controller = $event->getController();

        if (!\is_array($controller) && method_exists($controller, '__invoke')) {
            $controller = [$controller, '__invoke'];
        }

        if (!\is_array($controller)) {
            return;
        }

        $className = self::getRealClass(\get_class($controller[0]));
        $object = new \ReflectionClass($className);
        $method = $object->getMethod($controller[1]);

        $classConfigurations = $this->getConfigurations($this->reader->getClassAttributes($object));
        $methodConfigurations = $this->getConfigurations($this->reader->getMethodAttributes($method));

        /** Customization start */
        $classAttributes = array_map(
            function (\ReflectionAttribute $attribute) {
                return $attribute->newInstance();
            },
            $object->getAttributes(PHPAttributeConfigurationInterface::class, \ReflectionAttribute::IS_INSTANCEOF)
        );
        $classConfigurations = array_merge($classConfigurations, $this->getConfigurations($classAttributes));

        $methodAttributes = array_map(
            function (\ReflectionAttribute $attribute) {
                return $attribute->newInstance();
            },
            $method->getAttributes(PHPAttributeConfigurationInterface::class, \ReflectionAttribute::IS_INSTANCEOF)
        );
        $methodConfigurations = array_merge($methodConfigurations, $this->getConfigurations($methodAttributes));

        $configurations = [];
        foreach (array_merge(array_keys($classConfigurations), array_keys($methodConfigurations)) as $key) {
            if (!\array_key_exists($key, $classConfigurations)) {
                $configurations[$key] = $methodConfigurations[$key];
            } elseif (!\array_key_exists($key, $methodConfigurations)) {
                $configurations[$key] = $classConfigurations[$key];
            } else {
                if (\is_array($classConfigurations[$key])) {
                    if (!\is_array($methodConfigurations[$key])) {
                        throw new \UnexpectedValueException(
                            message: sprintf('Configurations should both be an array or both not be an array.')
                        );
                    }
                    $configurations[$key] = array_merge($classConfigurations[$key], $methodConfigurations[$key]);
                } else {
                    // method configuration overrides class configuration
                    $configurations[$key] = $methodConfigurations[$key];
                }
            }
        }

        $this->setRequestArguments($event, $configurations);
        /** Customization end */
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    private function getConfigurations(array $annotations): array
    {
        $configurations = [];
        foreach ($annotations as $configuration) {
            /** Customization start */
            if ($configuration instanceof PHPAttributeConfigurationInterface) {
                $key = '_' . $configuration->getAliasName();
                if ($configuration->allowArray()) {
                    $configurations[$key][] = $configuration;
                } elseif (!isset($configurations[$key])) {
                    $configurations[$key] = $configuration;
                } else {
                    throw new \LogicException(
                        message: sprintf('Multiple "%s" attributes are not allowed.', $configuration->getAliasName())
                    );
                }
            }
            /** Customization end */
        }

        return $configurations;
    }

    private function setRequestArguments(KernelEvent $event, array $configurations): void
    {
        foreach ($configurations as $key => $attributes) {
            $event->getRequest()->attributes->set($key, $attributes);
        }
    }

    private static function getRealClass(string $class): string
    {
        if (class_exists(Proxy::class)) {
            if (false === $pos = strrpos($class, '\\' . Proxy::MARKER . '\\')) {
                return $class;
            }

            return substr($class, $pos + Proxy::MARKER_LENGTH + 2);
        }

        return $class;
    }
}
