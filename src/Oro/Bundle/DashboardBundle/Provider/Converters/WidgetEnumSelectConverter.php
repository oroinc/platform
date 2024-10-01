<?php

namespace Oro\Bundle\DashboardBundle\Provider\Converters;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Converter for enum select widget.
 * @codeCoverageIgnore
 */
class WidgetEnumSelectConverter extends WidgetEntitySelectConverter
{
    public function __construct(
        AclHelper $aclHelper,
        EntityNameResolver $entityNameResolver,
        DoctrineHelper $doctrineHelper,
        EntityManager $entityManager,
        $enumCode
    ) {

        parent::__construct($aclHelper, $entityNameResolver, $doctrineHelper, $entityManager, EnumOption::class);
    }
}
