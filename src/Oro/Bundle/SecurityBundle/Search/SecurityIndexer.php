<?php

namespace Oro\Bundle\SecurityBundle\Search;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Security\Core\Util\ClassUtils;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SecurityBundle\Formatter\ShareFormatter;
use Oro\Bundle\SecurityBundle\Provider\ShareScopeProvider;
use Oro\Bundle\UserBundle\Entity\User;

class SecurityIndexer
{
    /** @var ObjectManager */
    protected $em;

    /** @var \Twig_Environment */
    protected $twig;

    /** @var ConfigManager */
    protected $configManager;

    /** @var Indexer */
    protected $indexer;

    /** @var ShareFormatter */
    protected $shareFormatter;

    /** @var ShareScopeProvider */
    protected $shareScopeProvider;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /**
     * @param ObjectManager $em
     * @param \Twig_Environment $twig
     * @param ConfigManager $configManager
     * @param Indexer $indexer
     * @param ShareFormatter $shareFormatter
     * @param ShareScopeProvider $shareScopeProvider
     */
    public function __construct(
        ObjectManager $em,
        \Twig_Environment $twig,
        ConfigManager $configManager,
        Indexer $indexer,
        ShareFormatter $shareFormatter,
        ShareScopeProvider $shareScopeProvider
    ) {
        $this->em = $em;
        $this->twig = $twig;
        $this->configManager = $configManager;
        $this->indexer = $indexer;
        $this->shareFormatter = $shareFormatter;
        $this->shareScopeProvider = $shareScopeProvider;
    }

    /**
     * @param User $user
     * @param string $entityClass
     * @param string $searchString
     * @param int $offset
     * @param int $maxResults
     *
     * @return array
     */
    public function searchSharingEntities(User $user, $entityClass, $searchString, $offset = 0, $maxResults = 10)
    {
        $this->init();
        $objects = $this->getObjects($user, $entityClass, $searchString, $offset, $maxResults);
        $rows = $this->getGroupedRows($this->getRows($objects), $entityClass);

        return $rows;
    }

    /**
     * Additional initialization on demand
     */
    protected function init()
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * Returns objects extracted from simple search
     *
     * @param User $user
     * @param string $entityClass
     * @param string $searchString
     * @param int $offset
     * @param int $maxResults
     *
     * @return array
     */
    protected function getObjects(User $user, $entityClass, $searchString, $offset, $maxResults)
    {
        $objects = [];
        if (!$this->configManager->hasConfig($entityClass)) {
            return $objects;
        }
        $classNames = $this->shareScopeProvider->getClassNamesBySharingScopeConfig($entityClass);
        if (!$classNames) {
            return $objects;
        }
        $tables = [];
        foreach ($classNames as $className) {
            $metadata = $this->em->getClassMetadata($className);
            $tables[] = $metadata->getTableName();
        }
        $searchResults = $this->indexer->simpleSearch($searchString, $offset, $maxResults, $tables);
        list($userIds, $buIds, $orgIds) = $this->getIdsByClass($searchResults, $user);

        if ($orgIds) {
            $organizations = $this->em->getRepository('OroOrganizationBundle:Organization')
                ->getEnabledOrganizations($orgIds);
            $objects = array_merge($objects, $organizations);
        }
        if ($buIds) {
            $businessUnits = $this->em->getRepository('OroOrganizationBundle:BusinessUnit')->getBusinessUnits($buIds);
            $objects = array_merge($objects, $businessUnits);
        }
        if ($userIds) {
            $users = $this->em->getRepository('OroUserBundle:User')->findUsersByIds($userIds);
            $objects = array_merge($objects, $users);
        }

        return $objects;
    }

    /**
     * Get id of entities excluded passed user
     *
     * @param Result $searchResults
     * @param User $user
     *
     * @return array
     */
    protected function getIdsByClass(Result $searchResults, User $user)
    {
        $userIds = $buIds = $orgIds = [];
        foreach ($searchResults->getElements() as $item) {
            $className = $item->getEntityName();
            if (ClassUtils::getRealClass($user) === $className && $user->getId() === $item->getRecordId()) {
                continue;
            }
            if ($className === 'Oro\Bundle\UserBundle\Entity\User') {
                $userIds[] = $item->getRecordId();
            } elseif ($className === 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit') {
                $buIds[] = $item->getRecordId();
            } elseif ($className === 'Oro\Bundle\OrganizationBundle\Entity\Organization') {
                $orgIds[] = $item->getRecordId();
            }
        }

        return [$userIds, $buIds, $orgIds];
    }

    /**
     * Returns rows which are structured for "oro_share_select" form type
     *
     * @param object[] $objects
     *
     * @return array
     */
    protected function getRows($objects)
    {
        $rows = [];
        foreach ($objects as $object) {
            $details = $this->shareFormatter->getEntityDetails($object);
            $rows[] = [
                'id' => json_encode([
                    'entityId' => (string) $this->propertyAccessor->getValue($object, 'id'),
                    'entityClass' => ClassUtils::getRealClass($object),
                ]),
                'text' => $details['label'],
                'recordId' => $this->propertyAccessor->getValue($object, 'id'),
                'classLabel' => $details['classLabel'],
                'entityClass' => ClassUtils::getRealClass($object),
                'entity' => $this->twig->render(
                    'OroSecurityBundle:Share:Property/entity.html.twig',
                    [
                        'value' => $details,
                    ]
                ),
            ];
        }

        return $rows;
    }

    /**
     * Returns rows grouped by class name
     *
     * @param array $rows
     * @param string $entityClass
     *
     * @return array
     */
    protected function getGroupedRows($rows, $entityClass)
    {
        $result = [];
        $classNames = $this->shareScopeProvider->getClassNamesBySharingScopeConfig($entityClass);
        if (!$classNames) {
            return $result;
        }
        foreach ($classNames as $className) {
            $children = [];
            $classLabel = '';
            foreach ($rows as $row) {
                if ($row['entityClass'] === $className) {
                    $classLabel = $row['classLabel'];
                    $child = $row;
                    $children[] = $child;
                }
            }
            if ($children) {
                $result[] = [
                    'text' => $classLabel,
                    'children' => $children,
                ];
            }
        }

        return $result;
    }
}
