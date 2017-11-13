<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Stub;

use Oro\Bundle\DataAuditBundle\Entity\AuditAdditionalFieldsInterface;

class EntityAdditionalFields implements AuditAdditionalFieldsInterface
{
    /** @var array  */
    private $fields;

    /**
     * @param array|null $fields
     */
    public function __construct($fields = [])
    {
        $this->fields = $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function getAdditionalFields()
    {
        return $this->fields;
    }
}
