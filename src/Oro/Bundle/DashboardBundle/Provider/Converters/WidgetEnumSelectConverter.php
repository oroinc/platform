<?php

namespace Oro\Bundle\DashboardBundle\Provider\Converters;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * @codeCoverageIgnore
 */
class WidgetEnumSelectConverter extends WidgetEntitySelectConverter
{
    /**
     * {@inheritdoc}
     */
    public function __construct(
        AclHelper $aclHelper,
        EntityNameResolver $entityNameResolver,
        DoctrineHelper $doctrineHelper,
        EntityManager $entityManager,
        $enumCode
    ) {
        $entityClass = ExtendHelper::buildEnumValueClassName($enumCode);

        parent::__construct($aclHelper, $entityNameResolver, $doctrineHelper, $entityManager, $entityClass);
    }
}
