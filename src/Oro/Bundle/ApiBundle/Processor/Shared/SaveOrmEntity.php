<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\ORM\EntityManager;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

abstract class SaveOrmEntity implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

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
        /** @var Context $context */

        if ($context->hasErrors()) {
            // data cannot be saved because some errors occurs
            return;
        }

        $entity = $context->getResult();
        if (!is_object($entity)) {
            // entity does not exist
            return;
        }

        $em = $this->doctrineHelper->getEntityManager($entity, false);
        if (!$em) {
            // only manageable entities are supported
            return;
        }

        $this->saveEntity($em, $entity);
    }

    /**
     * @param EntityManager $em
     * @param object        $entity
     */
    protected function saveEntity(EntityManager $em, $entity)
    {
        $em->flush($entity);
    }
}
