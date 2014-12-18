<?php

namespace Oro\Bundle\CommentBundle\Entity\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\CommentBundle\Entity\Repository\CommentRepository;
use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\DataGridBundle\Extension\Pager\Orm\Pager;
use Oro\Bundle\CommentBundle\Entity\Comment;
use Oro\Bundle\ConfigBundle\Config\UserConfigManager;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager as BaseApiEntityManager;
use Symfony\Component\EventDispatcher\EventDispatcher;

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

        $this->setEventDispatcher($eventDispatcher);

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
        $entityName = $this->convertRelationEntityClassName($entityClass);
        $result     = [];

        if ($this->isCorrectClassName($entityName)) {
            $fieldName = $this->getFieldName($entityName);

            /** @var QueryBuilder $qb */
            $qb = $this->getRepository()->getBaseQueryBuilder();

            $qb->andWhere('c.' . $fieldName . ' = :param1');
            $qb->setParameter('param1', (int)$entityId);

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

        $setter        = $this->getFieldSetter($entityName);
        $relatedEntity = $this->getRelatedEntity($entityName, $entityId);

        call_user_func([$entity, $setter], $relatedEntity);
    }

    /**
     * @param Comment $entity
     * @param  string $entityClass
     * @param  string $entityId
     *
     * @return array
     */
    public function getEntityViewModel(Comment $entity, $entityClass, $entityId)
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
     *
     * @return string
     */
    protected function getFieldSetter($entityName)
    {
        return 'set' . $this->prepareFieldName($entityName);
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
    protected function prepareFieldName($entityName)
    {
        $fieldName   = $this->getFieldName($entityName);
        $dividedName = explode('_', $fieldName);
        $result      = '';

        foreach ($dividedName as $row) {
            $result .= ucfirst($row);
        }

        return $result;
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
