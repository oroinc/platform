<?php

namespace Oro\Bundle\SecurityBundle\Migrations\Schema;

/**
 * Add query to set ownership type for entity to organization
 */
class SetOwnershipTypeQuery extends UpdateOwnershipTypeQuery
{
    /**
     * @var string
     */
    protected $className;

    /**
     * @var array
     */
    protected $ownershipData = [
        'owner_type' => 'ORGANIZATION',
        'owner_field_name' => 'owner',
        'owner_column_name' => 'owner_id'
    ];

    /**
     * @param string $className
     * @param array  $ownershipData
     */
    public function __construct($className, $ownershipData = [])
    {
        $this->className = $className;
        if (!empty($ownershipData)) {
            $this->ownershipData = array_merge($this->ownershipData, $ownershipData);
        }
    }

    /**
     * @param array $data
     * @return array
     */
    protected function getNewData($data)
    {
        $data['ownership'] = $this->ownershipData;

        return $data;
    }
}
