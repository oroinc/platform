<?php

namespace Oro\Bundle\DataAuditBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\DataAuditBundle\Model\ExtendAuditField;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * AuditField entity
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\DataAuditBundle\Entity\Repository\AuditFieldRepository")
 * @ORM\Table(name="oro_audit_field")
 * @Config(mode="hidden")
 */
class AuditField extends ExtendAuditField
{
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
}
