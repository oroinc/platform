<?php

namespace Oro\Bundle\ScopeBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroScopeBundle_Entity_Scope;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * Represents a set of application parameters that can be used to find application data suitable for these parameters.
 * @mixin OroScopeBundle_Entity_Scope
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_scope')]
#[ORM\UniqueConstraint(name: 'oro_scope_row_hash_uidx', columns: ['row_hash'])]
#[Config]
class Scope implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(name: 'row_hash', type: Types::STRING, length: 32, nullable: true)]
    private ?string $rowHash = null;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getRowHash()
    {
        return $this->rowHash;
    }

    /**
     * @param string $rowHash
     * @return Scope
     */
    public function setRowHash($rowHash)
    {
        $this->rowHash = $rowHash;

        return $this;
    }
}
