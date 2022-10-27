<?php
declare(strict_types=1);

namespace Oro\Bundle\SecurityBundle\Test;

use Oro\Bundle\SecurityBundle\Owner\OwnerTree;

/**
 * Provides read access to protected properties of the wrapped OwnerTree instance.
 */
final class OwnerTreeWrappingPropertiesAccessor extends OwnerTree
{
    /** @var OwnerTree */
    private $ownerTree;

    public function __construct(OwnerTree $ownerTree)
    {
        $this->ownerTree = $ownerTree;
    }

    public function xgetUserOwningOrganizationId(): array
    {
        return $this->ownerTree->userOwningOrganizationId;
    }

    public function xgetUserOwningBusinessUnitId(): array
    {
        return $this->ownerTree->userOwningBusinessUnitId;
    }

    public function xgetUserOrganizationIds(): array
    {
        return $this->ownerTree->userOrganizationIds;
    }

    public function xgetUserBusinessUnitIds(): array
    {
        return $this->ownerTree->userBusinessUnitIds;
    }

    public function xgetUserOrganizationBusinessUnitIds(): array
    {
        return $this->ownerTree->userOrganizationBusinessUnitIds;
    }

    public function xgetBusinessUnitOwningOrganizationId(): array
    {
        return $this->ownerTree->businessUnitOwningOrganizationId;
    }

    public function xgetAssignedBusinessUnitUserIds(): array
    {
        return $this->ownerTree->assignedBusinessUnitUserIds;
    }

    public function xgetSubordinateBusinessUnitIds(): array
    {
        return $this->ownerTree->subordinateBusinessUnitIds;
    }

    public function xgetOrganizationBusinessUnitIds(): array
    {
        return $this->ownerTree->organizationBusinessUnitIds;
    }
}
