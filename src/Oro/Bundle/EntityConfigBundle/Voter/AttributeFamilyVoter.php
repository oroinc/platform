<?php

namespace Oro\Bundle\EntityConfigBundle\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeFamilyManager;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;

class AttributeFamilyVoter extends AbstractEntityVoter
{
    const ATTRIBUTE_DELETE = 'delete';

    /** @var AttributeFamilyManager */
    private $familyManager;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param AttributeFamilyManager $familyManager
     */
    public function __construct(DoctrineHelper $doctrineHelper, AttributeFamilyManager $familyManager)
    {
        parent::__construct($doctrineHelper);
        $this->supportedAttributes = [self::ATTRIBUTE_DELETE];
        $this->className = AttributeFamily::class;
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
