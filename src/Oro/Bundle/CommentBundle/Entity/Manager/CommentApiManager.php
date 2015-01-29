<?php

namespace Oro\Bundle\CommentBundle\Entity\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\QueryBuilder;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\CommentBundle\Entity\Comment;
use Oro\Bundle\CommentBundle\Entity\Repository\CommentRepository;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\DataGridBundle\Extension\Pager\Orm\Pager;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

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

    /** @var ConfigManager */
    protected $config;

    /** @var AttachmentManager */
    protected $attachmentManager;

    /** @var aclHelper */
    protected $aclHelper;

    /**
     * @param Registry          $doctrine
     * @param SecurityFacade    $securityFacade
     * @param NameFormatter     $nameFormatter
     * @param Pager             $pager
     * @param ConfigManager $config
     * @param EventDispatcher   $eventDispatcher
     * @param AttachmentManager $attachmentManager
     * @param AclHelper         $aclHelper
     */
    public function __construct(
        Registry $doctrine,
        SecurityFacade $securityFacade,
        NameFormatter $nameFormatter,
        Pager $pager,
        ConfigManager $config,
        EventDispatcher $eventDispatcher,
        AttachmentManager $attachmentManager,
        AclHelper $aclHelper
    ) {
        $this->em                = $doctrine->getManager();
        $this->securityFacade    = $securityFacade;
        $this->nameFormatter     = $nameFormatter;
        $this->pager             = $pager;
        $this->config            = $config;
        $this->attachmentManager = $attachmentManager;
        $this->aclHelper         = $aclHelper;

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
            $qb->orderBy('c.createdAt', 'DESC');

            $pager = $this->pager;
            $pager->setQueryBuilder($qb);
            $pager->setPage($page);
            $pager->setMaxPerPage(self::ITEMS_PER_PAGE);
            $pager->init();

            $result['data']  = $this->getEntityViewModels($pager->getAppliedResult(), $entityClass, $entityId);
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
        $result = 0;

        if ($this->isCommentable()) {
            $entityName = $this->convertRelationEntityClassName($entityClass);
            try {
                if ($this->isCorrectClassName($entityName)) {
                    $result = $this->getBuildCommentCount($entityName, $entityId);
                }
            } catch (\Exception $e) {
            }
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
        $result = array_merge($result, $this->getAttachmentInfo($entity));

        return $result;
    }

    /**
     * @return bool
     */
    public function isCommentable()
    {
        return $this->securityFacade->isGranted('oro_comment_view');
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
        $repository    = $this->getObjectManager()->getRepository($entityName);
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

    /**
     * @param Comment $entity
     * @param File    $attachment
     *
     * @return string
     */
    protected function getAttachmentURL($entity, $attachment)
    {
        return $this->attachmentManager->getFileUrl($entity, 'attachment', $attachment, 'download', true);
    }

    /**
     * @param $entity
     *
     * @return File
     */
    protected function getAttachment($entity)
    {
        $accessor   = PropertyAccess::createPropertyAccessor();
        $attachment = $accessor->getValue($entity, 'attachment');

        return $attachment;
    }

    /**
     * @param Comment $entity
     *
     * @return array
     */
    protected function getAttachmentInfo(Comment $entity)
    {
        $result     = [];
        $attachment = $this->getAttachment($entity);
        if ($attachment) {
            $result = [
                'attachmentURL'      => $this->getAttachmentURL($entity, $attachment),
                'attachmentSize'     => $this->attachmentManager->getFileSize($attachment->getFileSize()),
                'attachmentFileName' => $attachment->getOriginalFilename(),
            ];
        }
        return $result;
    }

    /**
     * @param string $entityName
     * @param string $entityId
     *
     * @return int important to return int
     */
    protected function getBuildCommentCount($entityName, $entityId)
    {
        /** @var CommentRepository $repository */
        $fieldName  = $this->getFieldName($entityName);
        $repository = $this->getRepository();
        $qb         = $repository->getNumberOfComment($fieldName, $entityId);
        $query      = $this->aclHelper->apply($qb);

        return  (int) $query->getSingleScalarResult();
    }
}
