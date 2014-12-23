<?php

namespace Oro\Bundle\CommentBundle\Entity\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\QueryBuilder;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\CommentBundle\Entity\Comment;
use Oro\Bundle\ConfigBundle\Config\UserConfigManager;
use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\DataGridBundle\Extension\Pager\Orm\Pager;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\CommentBundle\Entity\Repository\CommentRepository;

class CommentApiManager extends ApiEntityManager
{
    const ITEMS_PER_PAGE = 10;

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
     * @param EventDispatcher   $eventDispatcher
     */
    public function __construct(
        Registry $doctrine,
        SecurityFacade $securityFacade,
        NameFormatter $nameFormatter,
        Pager $pager,
        UserConfigManager $config,
        EventDispatcher $eventDispatcher
    ) {
        $this->em             = $doctrine->getManager();
        $this->securityFacade = $securityFacade;
        $this->nameFormatter  = $nameFormatter;
        $this->pager          = $pager;
        $this->config         = $config;

        parent::__construct(Comment::ENTITY_NAME, $this->em);

        $this->setEventDispatcher($eventDispatcher);
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
        $entityName = $this->convertRelationEntityClassName($entityClass);
        $result     = [
            'count' => 0,
            'data'  => [],
        ];

        if ($this->isCorrectClassName($entityName)) {
            $fieldName = $this->getFieldName($entityName);

            /** @var CommentRepository $repository */
            $repository = $this->getRepository();

            /** @var QueryBuilder $qb */
            $qb = $repository->getBaseQueryBuilder($fieldName, $entityId);
            $qb->orderBy('c.updatedAt', 'DESC');

            $pager = $this->pager;
            $pager->setQueryBuilder($qb);
            $pager->setPage($page);
            $pager->setMaxPerPage(self::ITEMS_PER_PAGE);
            $pager->init();

            $result['data']  = $this->getEntityViewModels($pager->getResults(), $entityClass, $entityId);
            $result['count'] = $pager->getNbResults();
        }

        return $result;
    }

    /**
     * @param string $entityClass
     * @param string $entityId
     *
     * @return int
     */
    public function getCommentCount($entityClass, $entityId)
    {
        $entityName = $this->convertRelationEntityClassName($entityClass);
        $result     = 0;

        try {
            if ($this->isCorrectClassName($entityName)) {
                /** @var CommentRepository $repository */
                $fieldName  = $this->getFieldName($entityName);
                $repository = $this->getRepository();
                $result     = (int)$repository->getNumberOfComment($fieldName, $entityId);
            }
        } catch (\Exception $e) {
        }
        return $result;
    }

    /**
     * @param Comment $entity
     * @param string  $entityClass
     * @param string  $entityId
     */
    public function setRelationField(Comment $entity, $entityClass, $entityId)
    {
        $entityName = $this->convertRelationEntityClassName($entityClass);

        if (!$this->isCorrectClassName($entityName)) {
            throw new InvalidEntityException('Invalid entity name ' . $entityName);
        }

        $relatedEntity = $this->getRelatedEntity($entityName, $entityId);
        $accessor      = PropertyAccess::createPropertyAccessor();

        $accessor->setValue($entity, $this->getFieldName($entityName), $relatedEntity);
    }

    /**
     * @param Comment $entity
     * @param string  $entityClass
     * @param string  $entityId
     *
     * @return array
     */
    public function getEntityViewModel(Comment $entity, $entityClass = '', $entityId = '')
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
            'relationClass' => $entityClass,
            'relationId'    => $entityId,
            'createdAt'     => $entity->getCreatedAt()->format('c'),
            'updatedAt'     => $entity->getUpdatedAt()->format('c'),
            'editable'      => $this->securityFacade->isGranted('EDIT', $entity),
            'removable'     => $this->securityFacade->isGranted('DELETE', $entity),
        ];

        return $result;
    }

    /**
     * @param string $entityName
     * @param int    $entityId
     *
     * @return Object Returns instance of $entityName
     *
     * @throws EntityNotFoundException
     */
    protected function getRelatedEntity($entityName, $entityId)
    {
        $repository = $this->getObjectManager()->getRepository($entityName);

        $relatedEntity = $repository->findOneById($entityId);

        if (empty($relatedEntity)) {
            throw new EntityNotFoundException();
        }

        return $relatedEntity;
    }

    /**
     * @param string $entityName
     *
     * @return string
     */
    protected function getFieldName($entityName)
    {
        return ExtendHelper::buildAssociationName($entityName);
    }

    /**
     * @param string $entityClass
     *
     * @return string
     */
    protected function convertRelationEntityClassName($entityClass)
    {
        return str_replace('_', '\\', $entityClass);
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
     * @param string $entityName
     *
     * @return bool
     */
    protected function isCorrectClassName($entityName)
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
