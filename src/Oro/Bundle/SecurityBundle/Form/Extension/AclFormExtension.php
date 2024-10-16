<?php

namespace Oro\Bundle\SecurityBundle\Form\Extension;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\FormTypeInterface;

/**
 * Form extension decorator that adds AclProtectedFieldTypeExtension extension as first extension for all forms.
 */
class AclFormExtension implements FormExtensionInterface
{
    /** @var AclProtectedFieldTypeExtension */
    private $aclFieldExtension;

    /** @var FormExtensionInterface */
    private $innerExtension;

    /**
     * AclFormExtension constructor.
     */
    public function __construct(
        AclProtectedFieldTypeExtension $aclFieldExtension,
        FormExtensionInterface $innerExtension
    ) {
        $this->aclFieldExtension = $aclFieldExtension;
        $this->innerExtension = $innerExtension;
    }

    #[\Override]
    public function getType($name): FormTypeInterface
    {
        return $this->innerExtension->getType($name);
    }

    #[\Override]
    public function hasType($name): bool
    {
        return $this->innerExtension->hasType($name);
    }

    #[\Override]
    public function getTypeExtensions($name): array
    {
        $extensions = $this->innerExtension->getTypeExtensions($name);
        // register ACL field form extension as first for all forms except the parent FormType
        if ($name !== FormType::class) {
            array_unshift($extensions, $this->aclFieldExtension);
        }

        return $extensions;
    }

    #[\Override]
    public function hasTypeExtensions($name): bool
    {
        return $this->innerExtension->hasTypeExtensions($name);
    }

    #[\Override]
    public function getTypeGuesser(): ?FormTypeGuesserInterface
    {
        return $this->innerExtension->getTypeGuesser();
    }
}
