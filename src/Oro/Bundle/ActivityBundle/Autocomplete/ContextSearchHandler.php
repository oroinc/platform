<?php

namespace Oro\Bundle\ActivityBundle\Autocomplete;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\FormBundle\Autocomplete\ConverterInterface;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\Result\Item;

/**
 * This is specified handler that search targets entities for specified activity class.
 *
 * Can not use default Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface cause in this handler we manipulate
 * with different types of entities.
 *
 * Also @see Oro\Bundle\EmailBundle\Form\DataTransformer\ContextsToViewTransformer
 */
class ContextSearchHandler implements ConverterInterface
{
    /** @var TokenStorageInterface */
    protected $token;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var Indexer */
    protected $indexer;

    /** @var ActivityManager */
    protected $activityManager;

    /** @var ConfigManager */
    protected $configManager;

    /** @var EntityClassNameHelper */
    protected $entityClassNameHelper;

    /** @var string */
    protected $class;

    /**
     * @param TokenStorageInterface $token
     * @param TranslatorInterface   $translator
     * @param Indexer               $indexer
     * @param ActivityManager       $activityManager
     * @param ConfigManager         $configManager
     * @param EntityClassNameHelper $entityClassNameHelper
     * @param string|null           $class
     */
    public function __construct(
        TokenStorageInterface $token,
        TranslatorInterface $translator,
        Indexer $indexer,
        ActivityManager $activityManager,
        ConfigManager $configManager,
        EntityClassNameHelper $entityClassNameHelper,
        $class = null
    ) {
        $this->token                 = $token;
        $this->translator            = $translator;
        $this->indexer               = $indexer;
        $this->activityManager       = $activityManager;
        $this->configManager         = $configManager;
        $this->entityClassNameHelper = $entityClassNameHelper;
        $this->class                 = $class;
    }

    /**
     * @param string $class
     */
    public function setClass($class)
    {
        $this->class = $class;
    }

    /**
     * @param Item[] $items
     *
     * @return array
     */
    protected function convertItems(array $items)
    {
        $user = $this->token->getToken()->getUser();

        $result = [];
        /** @var Item $item */
        foreach ($items as $item) {
            // Exclude current user from result
            if (ClassUtils::getClass($user) === $item->getEntityName() && $user->getId() === $item->getRecordId()) {
                continue;
            }

            $result[] = $this->convertItem($item);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function convertItem($item)
    {
        /** @var Item $item */
        $text      = $item->getRecordTitle();
        $className = $item->getEntityName();
        if ($label = $this->getClassLabel($className)) {
            $text .= ' (' . $label . ')';
        }

        return [
            'id'   => json_encode(
                [
                    'entityClass' => $className,
                    'entityId'    => $item->getRecordId(),
                ]
            ),
            'text' => $text
        ];
    }

    /**
     * Gets label for the class
     *
     * @param string $className - FQCN
     *
     * @return string|null
     */
    protected function getClassLabel($className)
    {
        if (!$this->configManager->hasConfig($className)) {
            return null;
        }

        $label = $this->configManager->getProvider('entity')->getConfig($className)->get('label');

        return $this->translator->trans($label);
    }

    /**
     * {@inheritdoc}
     */
    public function search($query, $page, $perPage)
    {
        $page        = (int)$page > 0 ? (int)$page : 1;
        $perPage     = (int)$perPage > 0 ? (int)$perPage : 10;
        $firstResult = ($page - 1) * $perPage;
        $perPage++;

        $items = [];
        $from  = $this->getSearchAliases();
        if ($from) {
            $items = $this->indexer->simpleSearch(
                $query,
                $firstResult,
                $perPage,
                $from,
                $page
            )->getElements();
        }

        $hasMore = count($items) === $perPage;
        if ($hasMore) {
            $items = array_slice($items, 0, $perPage - 1);
        }

        return [
            'results' => $this->convertItems($items),
            'more'    => $hasMore
        ];
    }

    /**
     * Get search aliases for all entities which can be associated with specified activity.
     *
     * @return string[]
     */
    protected function getSearchAliases()
    {
        $class    = $this->entityClassNameHelper->resolveEntityClass($this->class, true);
        $aliases = [];
        foreach ($this->activityManager->getActivityTargets($class) as $targetEntityClass => $fieldName) {
            $alias = $this->indexer->getEntityAlias($targetEntityClass);
            if (null !== $alias) {
                $aliases[] = $alias;
            }
        }

        return $aliases;
    }
}
