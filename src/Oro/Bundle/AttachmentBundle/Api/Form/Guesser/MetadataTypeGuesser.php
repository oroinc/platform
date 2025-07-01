<?php

namespace Oro\Bundle\AttachmentBundle\Api\Form\Guesser;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Config\ConfigAccessorInterface;
use Oro\Bundle\ApiBundle\Form\Guesser\MetadataTypeGuesserInterface;
use Oro\Bundle\ApiBundle\Metadata\MetadataAccessorInterface;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityMapper;
use Oro\Bundle\AttachmentBundle\Api\Form\Type\FileEntityType;
use Oro\Bundle\AttachmentBundle\Api\Form\Type\MultiFileEntityType;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\Form\Guess\ValueGuess;

/**
 * Guesses form types for the following associations:
 * * file
 * * image
 * * multi file
 * * multi image
 */
class MetadataTypeGuesser implements MetadataTypeGuesserInterface
{
    public function __construct(
        private readonly MetadataTypeGuesserInterface $innerGuesser,
        private readonly DoctrineHelper $doctrineHelper
    ) {
    }

    #[\Override]
    public function getMetadataAccessor(): ?MetadataAccessorInterface
    {
        return $this->innerGuesser->getMetadataAccessor();
    }

    #[\Override]
    public function setMetadataAccessor(?MetadataAccessorInterface $metadataAccessor): void
    {
        $this->innerGuesser->setMetadataAccessor($metadataAccessor);
    }

    #[\Override]
    public function getConfigAccessor(): ?ConfigAccessorInterface
    {
        return $this->innerGuesser->getConfigAccessor();
    }

    #[\Override]
    public function setConfigAccessor(?ConfigAccessorInterface $configAccessor): void
    {
        $this->innerGuesser->setConfigAccessor($configAccessor);
    }

    #[\Override]
    public function getEntityMapper(): ?EntityMapper
    {
        return $this->innerGuesser->getEntityMapper();
    }

    #[\Override]
    public function setEntityMapper(?EntityMapper $entityMapper): void
    {
        $this->innerGuesser->setEntityMapper($entityMapper);
    }

    #[\Override]
    public function getIncludedEntities(): ?IncludedEntityCollection
    {
        return $this->innerGuesser->getIncludedEntities();
    }

    #[\Override]
    public function setIncludedEntities(?IncludedEntityCollection $includedEntities): void
    {
        $this->innerGuesser->setIncludedEntities($includedEntities);
    }

    #[\Override]
    public function guessType(string $class, string $property): ?TypeGuess
    {
        $association = $this->getMetadataAccessor()?->getMetadata($class)?->getAssociation($property);
        if (null !== $association && File::class === $association->getTargetClassName()) {
            if (!$association->isCollection()) {
                return new TypeGuess(
                    FileEntityType::class,
                    [
                        'metadata' => $association,
                        'entity_mapper' => $this->getEntityMapper(),
                        'included_entities' => $this->getIncludedEntities()
                    ],
                    TypeGuess::HIGH_CONFIDENCE
                );
            }
            if ($this->isMultiFileAssociation($class, $property)) {
                return new TypeGuess(
                    MultiFileEntityType::class,
                    [
                        'metadata' => $association,
                        'entity_mapper' => $this->getEntityMapper(),
                        'included_entities' => $this->getIncludedEntities()
                    ],
                    TypeGuess::HIGH_CONFIDENCE
                );
            }
        }

        return $this->innerGuesser->guessType($class, $property);
    }

    #[\Override]
    public function guessRequired(string $class, string $property): ?ValueGuess
    {
        return $this->innerGuesser->guessRequired($class, $property);
    }

    #[\Override]
    public function guessMaxLength(string $class, string $property): ?ValueGuess
    {
        return $this->innerGuesser->guessMaxLength($class, $property);
    }

    #[\Override]
    public function guessPattern(string $class, string $property): ?ValueGuess
    {
        return $this->innerGuesser->guessPattern($class, $property);
    }

    private function isMultiFileAssociation(string $class, string $property): bool
    {
        $entityMetadata = $this->doctrineHelper->getEntityMetadataForClass($class, false);
        if (null === $entityMetadata) {
            return false;
        }
        if (!$entityMetadata->hasAssociation($property)) {
            return false;
        }
        if (FileItem::class !== $entityMetadata->getAssociationTargetClass($property)) {
            return false;
        }

        return true;
    }
}
