<?php

namespace Oro\Bundle\UserBundle\Autocomplete;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Autocomplete\QueryCriteria\SearchCriteria;
use Oro\Bundle\UserBundle\Dashboard\OwnerHelper;
use Symfony\Component\Translation\TranslatorInterface;

class WidgetUserSearchHandler extends UserSearchHandler
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var bool */
    protected $addCurrent = false;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var SearchCriteria */
    protected $searchUserCriteria;

    /**
     * @param TranslatorInterface $translator
     * @param AttachmentManager   $attachmentManager
     * @param string              $userEntityName
     * @param array               $properties
     */
    public function __construct(
        TranslatorInterface $translator,
        AttachmentManager $attachmentManager,
        $userEntityName,
        array $properties
    ) {
        parent::__construct($attachmentManager, $userEntityName, $properties);

        $this->translator = $translator;
    }

    /**
     * @param TokenAccessorInterface $tokenAccessor
     */
    public function setTokenAccessor(TokenAccessorInterface $tokenAccessor)
    {
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * @param SearchCriteria $searchCriteria
     */
    public function setSearchUserCriteria(SearchCriteria $searchCriteria)
    {
        $this->searchUserCriteria = $searchCriteria;
    }

    /**
     * {@inheritdoc}
     */
    public function search($query, $page, $perPage, $searchById = false)
    {
        $page = (int)$page > 0 ? (int)$page : 1;
        if ($page === 1) {
            $this->addCurrent = true;
        }

        return parent::search($query, $page, $perPage, $searchById);
    }

    /**
     * {@inheritdoc}
     */
    protected function convertItems(array $items)
    {
        $result = parent::convertItems($items);

        $current = array_filter(
            $result,
            function ($item) {
                return $item[$this->idFieldName] === OwnerHelper::CURRENT_USER;
            }
        );
        if (empty($current) && $this->addCurrent) {
            $current = [
                $this->idFieldName => OwnerHelper::CURRENT_USER,
                'fullName'         => $this->translator->trans('oro.user.dashboard.current_user'),
            ];
            array_unshift($result, $current);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function convertItem($item)
    {
        if ($this->idFieldName) {
            if (is_array($item)) {
                if ($item[$this->idFieldName] === OwnerHelper::CURRENT_USER) {
                    $current = [
                        $this->idFieldName => OwnerHelper::CURRENT_USER,
                        'fullName'         => $this->translator->trans('oro.user.dashboard.current_user'),
                    ];

                    return $current;
                }
            }
        }

        return parent::convertItem($item);
    }

    /**
     * {@inheritdoc}
     */
    protected function searchEntities($search, $firstResult, $maxResults)
    {
        $queryBuilder = $this->getBasicQueryBuilder();
        if ($search) {
            $this->searchUserCriteria->addSearchCriteria($queryBuilder, $search);
        }
        $queryBuilder
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResults);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * {@inheritdoc}
     */
    protected function getBasicQueryBuilder()
    {
        $queryBuilder = $this->entityRepository->createQueryBuilder('u');
        $queryBuilder->leftJoin('u.organizations', 'org')
            ->andWhere('org.id = :org')
            ->andWhere('u.enabled = :enabled')
            ->setParameter('org', $this->tokenAccessor->getOrganizationId())
            ->setParameter('enabled', true);

        return $queryBuilder;
    }
}
