<?php

namespace Oro\Bundle\ApiBundle\Form\Guesser;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Config\ConfigAccessorInterface;
use Oro\Bundle\ApiBundle\Metadata\MetadataAccessorInterface;
use Oro\Bundle\ApiBundle\Util\EntityMapper;
use Symfony\Component\Form\FormTypeGuesserInterface;

/**
 * Represents a guesser that guess a form type based on "form_type_guesses" configuration and API metadata.
 */
interface MetadataTypeGuesserInterface extends FormTypeGuesserInterface
{
    public function getMetadataAccessor(): ?MetadataAccessorInterface;

    public function setMetadataAccessor(?MetadataAccessorInterface $metadataAccessor): void;

    public function getConfigAccessor(): ?ConfigAccessorInterface;

    public function setConfigAccessor(?ConfigAccessorInterface $configAccessor): void;

    public function getEntityMapper(): ?EntityMapper;

    public function setEntityMapper(?EntityMapper $entityMapper): void;

    public function getIncludedEntities(): ?IncludedEntityCollection;

    public function setIncludedEntities(?IncludedEntityCollection $includedEntities): void;
}
