<?php

namespace Oro\Bundle\EntityConfigBundle\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeFamilyManager;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;

/**
 * Prevents removal of non deletable attribute families.
 */
class AttributeFamilyVoter extends AbstractEntityVoter
{
    const ATTRIBUTE_DELETE = 'delete';

    /** @var array */
    protected $supportedAttributes = [self::ATTRIBUTE_DELETE];

    /** @var AttributeFamilyManager */
    private $familyManager;

    public function __construct(DoctrineHelper $doctrineHelper, AttributeFamilyManager $familyManager)
    {
        parent::__construct($doctrineHelper);
        $this->familyManager = $familyManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        return $this->familyManager->isAttributeFamilyDeletable($identifier) ?
            self::ACCESS_ABSTAIN :
            self::ACCESS_DENIED;
    }
}
