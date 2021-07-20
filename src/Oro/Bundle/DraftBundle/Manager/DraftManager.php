<?php

namespace Oro\Bundle\DraftBundle\Manager;

use DeepCopy\DeepCopy;
use Oro\Bundle\DraftBundle\Duplicator\DraftContext;
use Oro\Bundle\DraftBundle\Duplicator\Extension\DuplicatorExtensionInterface;
use Oro\Bundle\DraftBundle\Duplicator\ExtensionProvider;
use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Component\ConfigExpression\ContextAccessor;

/**
 * Responsible for creating a draft, creating a draft from an existing draft, and publishing a draft.
 * Ensures that DeepCopy and extensions work properly.
 */
class DraftManager
{
    public const ACTION_CREATE_DRAFT = 'create_draft';
    public const ACTION_PUBLISH_DRAFT = 'publish_draft';

    /**
     * @var ExtensionProvider
     */
    private $extensionProvider;

    /**
     * @var ContextAccessor
     */
    private $contextAccessor;

    /**
     * @var Publisher
     */
    private $publisher;

    public function __construct(
        ExtensionProvider $extensionProvider,
        ContextAccessor $contextAccessor,
        Publisher $publisher
    ) {
        $this->extensionProvider = $extensionProvider;
        $this->contextAccessor = $contextAccessor;
        $this->publisher = $publisher;
    }

    /**
     * @param DraftableInterface $source
     * @param \ArrayAccess $context
     *
     * @return DraftableInterface
     */
    public function createDraft(DraftableInterface $source, \ArrayAccess $context = null): DraftableInterface
    {
        $context = $context ?? $this->createContext();
        $this->contextAccessor->setValue($context, 'action', self::ACTION_CREATE_DRAFT);
        $copier = $this->getDeepCopy($source, $context);

        return $copier->copy($source);
    }

    /**
     * @param DraftableInterface $source
     * @param \ArrayAccess $context
     *
     * @return DraftableInterface
     */
    public function createPublication(DraftableInterface $source, \ArrayAccess $context = null): DraftableInterface
    {
        $context = $context ?? $this->createContext();
        $this->contextAccessor->setValue($context, 'action', self::ACTION_PUBLISH_DRAFT);
        $copier = $this->getDeepCopy($source, $context);

        /**
         * For draft publication, it is necessary to copy the draft, this allow within the same table to solve
         * problems with duplicates and copy data to the source entity without complicated manipulation of
         * relationships, unique fields, etc.
         */
        $source = $copier->copy($source);

        return $this->publisher->create($source);
    }

    private function getDeepCopy(DraftableInterface $source, \ArrayAccess $context): DeepCopy
    {
        // Context options are used in the draft extension
        $this->contextAccessor->setValue($context, 'source', $source);
        $deepCopy = new DeepCopy();

        /** @var DuplicatorExtensionInterface $extension */
        foreach ($this->extensionProvider->getExtensions() as $extension) {
            $extension->setContext($context);
            if ($extension->isSupport($source)) {
                $deepCopy->addFilter($extension->getFilter(), $extension->getMatcher());
            }
        }

        return $deepCopy;
    }

    private function createContext(): DraftContext
    {
        return new DraftContext();
    }
}
