<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor;

use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class SetTestCurrentDepartmentEntityIdForSubresource implements ProcessorInterface
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SubresourceContext $context */

        $rows = $this->doctrineHelper
            ->getEntityRepositoryForClass(TestDepartment::class)
            ->createQueryBuilder('e')
            ->select('e.id')
            ->where('e.name = :name')
            ->setParameter('name', 'Current Department')
            ->getQuery()
            ->getArrayResult();
        if (!empty($rows)) {
            $context->setParentId((string)$rows[0]['id']);
        }
    }
}
