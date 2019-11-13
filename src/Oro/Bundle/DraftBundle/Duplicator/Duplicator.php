<?php

namespace Oro\Bundle\DraftBundle\Duplicator;

use DeepCopy\DeepCopy;
use Oro\Bundle\DraftBundle\Duplicator\Extension\DuplicatorExtensionInterface;
use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Component\ConfigExpression\ContextAccessor;

/**
 * Duplicates an existing object and uses extensions to change the object parameters.
 */
class Duplicator
{
    /**
     * @var ExtensionProvider
     */
    private $extensionProvider;

    /**
     * @var ContextAccessor
     */
    private $contextAccessor;

    /**
     * @param ExtensionProvider $extensionProvider
     * @param ContextAccessor $contextAccessor
     */
    public function __construct(ExtensionProvider $extensionProvider, ContextAccessor $contextAccessor)
    {
        $this->extensionProvider = $extensionProvider;
        $this->contextAccessor = $contextAccessor;
    }

    /**
     * @param DraftableInterface $source
     * @param \ArrayAccess $context
     *
     * @return DraftableInterface
     */
    public function duplicate(DraftableInterface $source, \ArrayAccess $context): DraftableInterface
    {
        $this->contextAccessor->setValue($context, 'source', $source);

        $deepCopy = new DeepCopy();
        /** @var DuplicatorExtensionInterface $extension */
        foreach ($this->extensionProvider->getExtensions() as $extension) {
            $extension->setContext($context);
            if ($extension->isSupport($source)) {
                $deepCopy->addFilter($extension->getFilter(), $extension->getMatcher());
            }
        }

        return $deepCopy->copy($source);
    }
}
