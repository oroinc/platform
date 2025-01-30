<?php

namespace Oro\Component\Testing\Unit;

use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\PreloadedExtension as BasePreloadExtension;

/**
 * This is a temporary extension is used until all form types are refactored to not use stubs.
 * It allows to register stubs under original form type class names.
 */
class PreloadedExtension extends BasePreloadExtension
{
    /**
     * @var array
     */
    private $types = [];

    public function __construct(array $types, array $typeExtensions, ?FormTypeGuesserInterface $typeGuesser = null)
    {
        foreach ($types as $key => $type) {
            if (is_string($key)) {
                $this->types[$key] = $type;
            }
        }

        parent::__construct($types, $typeExtensions, $typeGuesser);
    }

    #[\Override]
    public function getType($name): FormTypeInterface
    {
        if (isset($this->types[$name])) {
            return $this->types[$name];
        }

        return parent::getType($name);
    }

    #[\Override]
    public function hasType($name): bool
    {
        return isset($this->types[$name]) || parent::hasType($name);
    }
}
