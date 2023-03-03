<?php

namespace Oro\Bundle\DataAuditBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * AuditField entity
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\DataAuditBundle\Entity\Repository\AuditFieldRepository")
 * @ORM\Table(name="oro_audit_field")
 * @Config(mode="hidden")
 */
class AuditField extends AbstractAuditField implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    /**
     * @var Audit
     *
     * @ORM\ManyToOne(
     *      targetEntity="Oro\Bundle\DataAuditBundle\Entity\AbstractAudit",
     *      inversedBy="fields",
     *      cascade={"persist"}
     * )
     * @ORM\JoinColumn(name="audit_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $audit;

    public function __construct($field, $dataType, $newValue, $oldValue)
    {
        parent::__construct($field, $dataType, $newValue, $oldValue);
    }
}
