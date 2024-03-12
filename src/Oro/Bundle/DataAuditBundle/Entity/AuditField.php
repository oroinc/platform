<?php

namespace Oro\Bundle\DataAuditBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroDataAuditBundle_Entity_AuditField;
use Oro\Bundle\DataAuditBundle\Entity\Repository\AuditFieldRepository;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * AuditField entity
 *
 * @mixin OroDataAuditBundle_Entity_AuditField
 */
#[ORM\Entity(repositoryClass: AuditFieldRepository::class)]
#[ORM\Table(name: 'oro_audit_field')]
#[Config(mode: 'hidden')]
class AuditField extends AbstractAuditField implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\ManyToOne(targetEntity: AbstractAudit::class, cascade: ['persist'], inversedBy: 'fields')]
    #[ORM\JoinColumn(name: 'audit_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?AbstractAudit $audit = null;

    public function __construct($field, $dataType, $newValue, $oldValue)
    {
        parent::__construct($field, $dataType, $newValue, $oldValue);
    }
}
