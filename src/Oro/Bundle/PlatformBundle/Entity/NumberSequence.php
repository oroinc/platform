<?php

declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\PlatformBundle\Entity\Repository\NumberSequenceRepository;

/**
 * Entity that manages sequential number generation with support for different sequence types and discriminators.
 */
#[ORM\Entity(repositoryClass: NumberSequenceRepository::class)]
#[ORM\Table(name: 'oro_number_sequence')]
#[ORM\UniqueConstraint(
    name: 'oro_sequence_uidx',
    columns: ['sequence_type', 'discriminator_type', 'discriminator_value']
)]
#[Config(mode: 'hidden')]
class NumberSequence implements DatesAwareInterface
{
    use DatesAwareTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * The type of the sequence.
     * It can be a string representing the type of sequence (e.g., 'order').
     */
    #[ORM\Column(name: 'sequence_type', type: Types::STRING, length: 255, nullable: false)]
    private ?string $sequenceType = null;

    /**
     * The type of the discriminator.
     * It can be a string representing the type of sequence (e.g., 'organization_periodic').
     */
    #[ORM\Column(name: 'discriminator_type', type: Types::STRING, length: 255, nullable: false)]
    private ?string $discriminatorType = null;

    /**
     * The value of the discriminator.
     * It can be a string representing the specific value for the sequence type.
     * For example, '42:2023-01', where 42 is organization id and 2023-01 is date.
     */
    #[ORM\Column(name: 'discriminator_value', type: Types::STRING, length: 255, nullable: false)]
    private ?string $discriminatorValue = null;

    /**
     * The sequential number itself.
     */
    #[ORM\Column(name: 'number', type: Types::INTEGER, nullable: false)]
    private ?int $number = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSequenceType(): ?string
    {
        return $this->sequenceType;
    }

    public function setSequenceType(string $sequenceType): self
    {
        $this->sequenceType = $sequenceType;

        return $this;
    }

    public function getDiscriminatorType(): ?string
    {
        return $this->discriminatorType;
    }

    public function setDiscriminatorType(string $discriminatorType): self
    {
        $this->discriminatorType = $discriminatorType;

        return $this;
    }

    public function getDiscriminatorValue(): ?string
    {
        return $this->discriminatorValue;
    }

    public function setDiscriminatorValue(string $discriminatorValue): self
    {
        $this->discriminatorValue = $discriminatorValue;

        return $this;
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(int $number): self
    {
        $this->number = $number;

        return $this;
    }
}
