<?php

namespace Oro\Bundle\DataAuditBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="oro_audit_field")
 */
class AuditField extends AbstractAuditField
{
    /**
     * @var Audit
     *
     * @ORM\ManyToOne(targetEntity="Audit", inversedBy="fields", cascade={"persist"})
     * @ORM\JoinColumn(name="audit_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $audit;
}
