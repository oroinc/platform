<?php

namespace Oro\Bundle\EntityExtendBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormTypeExtensionInterface;
use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\FormTypeInterface;

/**
 * Ensures that the extension of dynamic fields will be called by the latter,
 * regardless priority of form or extension.
 */
class EntityExtendFormExtension implements FormExtensionInterface
{
    /**
     * @var FormTypeExtensionInterface
     */
    private $extension;

    /**
     * @var FormExtensionInterface
     */
    private $innerExtension;

    /**
     * @param FormTypeExtensionInterface $extension
     * @param FormExtensionInterface $formExtension
     */
    public function __construct(FormTypeExtensionInterface $extension, FormExtensionInterface $formExtension)
    {
        $this->extension = $extension;
        $this->innerExtension = $formExtension;
    }

    /**
     * @param string $name
     *
     * @return FormTypeInterface
     */
    public function getType($name): FormTypeInterface
    {
        return $this->innerExtension->getType($name);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasType($name): bool
    {
        return $this->innerExtension->hasType($name);
    }

    /**
     * @param string $name
     *
     * @return array
     */
    public function getTypeExtensions($name): array
    {
        $extensions = $this->innerExtension->getTypeExtensions($name);

        /**
         * Added extensions to all forms except FormType.
         * This allows you to work around restrictions on form extensions and priorities.
         */
        if ($name !== FormType::class) {
            $extensions[] = $this->extension;
        }

        return $extensions;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasTypeExtensions($name): bool
    {
        return $this->innerExtension->hasTypeExtensions($name);
    }

    /**
     * @return FormTypeGuesserInterface|null
     */
    public function getTypeGuesser(): ?FormTypeGuesserInterface
    {
        return $this->innerExtension->getTypeGuesser();
    }
}
