<?php

namespace Oro\Bundle\ApiBundle\Form;

use Psr\Container\ContainerInterface;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormTypeGuesserChain;
use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\FormTypeInterface;

/**
 * Provides all form types, type extensions and guessers that can be used in API forms.
 */
class FormExtension implements FormExtensionInterface
{
    private ContainerInterface $container;
    /** @var array [form type name => service id or NULL, ...] */
    private array $types;
    /** @var array [extended form type name => [service id, ...], ...] */
    private array $typeExtensions;
    /** @var string[] [service id, ...] */
    private array $guessers;
    private ?FormTypeGuesserInterface $guesser = null;
    private bool $guesserLoaded = false;

    /**
     * @param ContainerInterface $container
     * @param array              $types          [form type name => service id or NULL, ...]
     * @param array              $typeExtensions [extended form type => [service id, ...], ...]
     * @param string[]           $guessers       [service id, ...]
     */
    public function __construct(ContainerInterface $container, array $types, array $typeExtensions, array $guessers)
    {
        $this->container = $container;
        $this->types = $types;
        $this->typeExtensions = $typeExtensions;
        $this->guessers = $guessers;
    }

    /**
     * {@inheritDoc}
     */
    public function getType($name)
    {
        if (!\array_key_exists($name, $this->types)) {
            throw new InvalidArgumentException(sprintf(
                'The form type "%s" is not registered.',
                $name
            ));
        }

        $serviceId = $this->types[$name];
        if (null === $serviceId) {
            if (!class_exists($name)) {
                throw new InvalidArgumentException(sprintf(
                    'Could not load form type "%s": class does not exist.',
                    $name
                ));
            }
            if (!is_subclass_of($name, FormTypeInterface::class)) {
                throw new InvalidArgumentException(sprintf(
                    'Could not load form type "%s": class does not implement "%s".',
                    $name,
                    FormTypeInterface::class
                ));
            }

            return new $name();
        }

        return $this->container->get($serviceId);
    }

    /**
     * {@inheritDoc}
     */
    public function hasType($name)
    {
        return \array_key_exists($name, $this->types);
    }

    /**
     * {@inheritDoc}
     */
    public function getTypeExtensions($name)
    {
        $extensions = [];

        if (isset($this->typeExtensions[$name])) {
            foreach ($this->typeExtensions[$name] as $serviceId) {
                $extension = $this->container->get($serviceId);
                $extensions[] = $extension;

                // validate result of getExtendedType() to ensure it is consistent with the service definition
                if (!\in_array($name, $extension->getExtendedTypes(), true)) {
                    throw new InvalidArgumentException(sprintf(
                        'The extended type specified for the service "%s" does not match the actual extended type.'
                        . ' Expected "%s", given "%s".',
                        $serviceId,
                        $name,
                        implode(', ', $extension->getExtendedTypes())
                    ));
                }
            }
        }

        return $extensions;
    }

    /**
     * {@inheritDoc}
     */
    public function hasTypeExtensions($name)
    {
        return isset($this->typeExtensions[$name]);
    }

    /**
     * {@inheritDoc}
     */
    public function getTypeGuesser()
    {
        if (!$this->guesserLoaded) {
            $this->guesserLoaded = true;
            $guessers = [];
            foreach ($this->guessers as $serviceId) {
                $guessers[] = $this->container->get($serviceId);
            }
            if (\count($guessers) > 0) {
                $this->guesser = new FormTypeGuesserChain($guessers);
            }
        }

        return $this->guesser;
    }
}
