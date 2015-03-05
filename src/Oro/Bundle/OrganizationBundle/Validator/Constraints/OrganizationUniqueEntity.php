<?php

namespace Oro\Bundle\OrganizationBundle\Validator\Constraints;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Constraint for the Unique Entity validator by organization.
 *
 * @Annotation
 * @Target({"CLASS", "ANNOTATION"})
 */
class OrganizationUniqueEntity extends UniqueEntity
{
    /**
     * {@inheritdoc}
     */
    public function __construct($options = null)
    {
        $this->service = 'organization_unique';

        parent::__construct($options);
    }
}
