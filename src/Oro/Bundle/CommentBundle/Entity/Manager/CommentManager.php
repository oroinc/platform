<?php

namespace Oro\Bundle\CommentBundle\Entity\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\CommentBundle\Entity\Repository\CommentRepository;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\DataGridBundle\Extension\Pager\Orm\Pager;
use Oro\Bundle\CommentBundle\Entity\Comment;
use Oro\Bundle\ConfigBundle\Config\UserConfigManager;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager as BaseApiEntityManager;

class CommentManager extends BaseApiEntityManager
{
    /** @var ObjectManager */
    protected $em;

    /** @var Pager */
    protected $pager;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var NameFormatter */
    protected $nameFormatter;

    /** @var UserConfigManager */
    protected $config;

    /**
     * @param Registry          $doctrine
     * @param SecurityFacade    $securityFacade
     * @param NameFormatter     $nameFormatter
     * @param Pager             $pager
     * @param UserConfigManager $config
     */
    public function __construct(
        Registry $doctrine,
        SecurityFacade $securityFacade,
        NameFormatter $nameFormatter,
        Pager $pager,
        UserConfigManager $config
    ) {
        $this->em             = $doctrine->getManager();
        $this->securityFacade = $securityFacade;
        $this->nameFormatter  = $nameFormatter;
        $this->pager          = $pager;
        $this->config         = $config;

        parent::__construct(Comment::ENTITY_NAME, $this->em);
    }

    /**
     * @param string $entityClass
     * @param int    $entityId
     * @param int    $page
     *
     * @return array
     */
    public function getCommentList($entityClass, $entityId, $page = 1)
    {
        $entityName = str_replace('_', '\\', $entityClass);
        $result     = [];

        if ($this->isCorrectClassName($entityName)) {
            $fieldName = ExtendHelper::buildAssociationName($entityName);

            /** @var QueryBuilder $qb */
            $qb = $this->getRepository()->getBaseQueryBuilder();

            $qb->andWhere('c.' . $fieldName . ' = :param1');
            $qb->setParameter('param1', $entityId);

            $pager = $this->pager;
            $pager->setQueryBuilder($qb);
            $pager->setPage($page);
            $pager->setMaxPerPage(10);
            $pager->init();

            $result = $this->getEntityViewModels($pager->getResults(), $entityClass, $entityId);
        }

        return $result;
    }

    /**
     * @param Comment[] $entities
     * @param string    $entityClass
     * @param string    $entityId
     *
     * @return array
     */
    protected function getEntityViewModels($entities, $entityClass, $entityId)
    {
        $result = [];

        foreach ($entities as $entity) {
            $result[] = $this->getEntityViewModel($entity, $entityClass, $entityId);
        }

        return $result;
    }

    /**
     * @param Comment $entity
     * @param  string $entityClass
     * @param  string $entityId
     *
     * @return array
     */
    protected function getEntityViewModel(Comment $entity, $entityClass, $entityId)
    {
        $ownerName = '';
        $ownerId   = '';

        if ($entity->getOwner()) {
            $ownerName = $this->nameFormatter->format($entity->getOwner());
            $ownerId   = $entity->getOwner()->getId();
        }

        $editorName = '';
        $editorId   = '';

        if ($entity->getUpdatedBy()) {
            $editorName = $this->nameFormatter->format($entity->getUpdatedBy());
            $editorId   = $entity->getUpdatedBy()->getId();
        }

        $result = [
            'id'            => $entity->getId(),
            'owner'         => $ownerName,
            'owner_id'      => $ownerId,
            'editor'        => $editorName,
            'editor_id'     => $editorId,
            'message'       => $entity->getMessage(),
            'activityClass' => $entityClass,
            'activityId'    => $entityId,
            'createdAt'     => $entity->getCreatedAt()->format('c'),
            'updatedAt'     => $entity->getUpdatedAt()->format('c'),
            'editable'      => $this->securityFacade->isGranted('EDIT', $entity),
            'removable'     => $this->securityFacade->isGranted('DELETE', $entity),
        ];

        return $result;
    }

    /**
     * @param string $entityName
     *
     * @return bool
     */
    public function isCorrectClassName($entityName)
    {
        try {
            $classMetadata = $this->em->getMetadataFactory()->getMetadataFor($entityName);
            $classMetadata->getName();
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}
