<?php

namespace Oro\Bundle\SecurityBundle\Form\Extension;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormExtensionInterface;

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

    /**
     * {@inheritDoc}
     */
    public function getType($name)
    {
        return $this->innerExtension->getType($name);
    }

    /**
     * {@inheritDoc}
     */
    public function hasType($name)
    {
        return $this->innerExtension->hasType($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getTypeExtensions($name)
    {
        $extensions = $this->innerExtension->getTypeExtensions($name);
        // register ACL field form extension as first for all forms except the parent FormType
        if ($name !== FormType::class) {
            array_unshift($extensions, $this->aclFieldExtension);
        }

        return $extensions;
    }

    /**
     * {@inheritDoc}
     */
    public function hasTypeExtensions($name)
    {
        return $this->innerExtension->hasTypeExtensions($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getTypeGuesser()
    {
        return $this->innerExtension->getTypeGuesser();
    }
}
