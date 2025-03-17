<?php

namespace Oro\Bundle\DashboardBundle\Provider\Converters;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * The dashboard widget configuration converter for select an enum entity.
 */
class WidgetEnumSelectConverter extends WidgetEntitySelectConverter
{
    public function __construct(
        AclHelper $aclHelper,
        EntityNameResolver $entityNameResolver,
        DoctrineHelper $doctrineHelper
    ) {
        parent::__construct($aclHelper, $entityNameResolver, $doctrineHelper, EnumOption::class);
    }
}
