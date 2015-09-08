<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ActivityBundle\Entity\Manager\ActivitySearchApiEntityManager;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\SearchBundle\Engine\Indexer as SearchIndexer;
use Oro\Bundle\SearchBundle\Query\Result as SearchResult;
use Oro\Bundle\SearchBundle\Query\Result\Item as SearchResultItem;
use Oro\Bundle\SearchBundle\Query\Query as QueryBuilder;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailRepository;

class EmailActivitySuggestionApiEntityManager extends ActivitySearchApiEntityManager
{
    /**
     * @param string          $class
     * @param ObjectManager   $om
     * @param ActivityManager $activityManager
     * @param SearchIndexer   $searchIndexer
     */
    public function __construct(
        $class,
        ObjectManager $om,
        ActivityManager $activityManager,
        SearchIndexer $searchIndexer
    ) {
        parent::__construct($om, $activityManager, $searchIndexer);
        $this->setClass($class);
    }

    /**
     * Gets suggestion result.
     *
     * @param int $emailId
     * @param int $page
     * @param int $limit
     *
     * @return array
     */
    public function getSuggestionResult(
        $emailId,
        $page = 1,
        $limit = 10
    ) {
        /** @var Email $email */
        $email = $this->find($emailId);
        if (!$email) {
            throw new NotFoundHttpException();
        }

        $data = $this->diff(
            $this->getSuggestionEntities($email),
            $this->getAssociatedEntities($email)
        );

        $slice = array_slice(
            $data,
            $this->getOffset($page, $limit),
            $limit
        );

        $result = [
            'result'     => $slice,
            'totalCount' =>
                function () use ($data) {
                    return count($data);
                }
        ];

        return $result;
    }

    /**
     * @param Email $email
     *
     * @return SearchResult
     */
    protected function getSuggestionEntities(Email $email)
    {
        $searchQueryBuilder = $this->searchIndexer->getSimpleSearchQuery(
            false,
            0,
            0,
            $this->getSearchAliases([])
        );
        $this->prepareSearchCriteria($searchQueryBuilder, $this->getEmails($email));

        return $this->searchIndexer->query($searchQueryBuilder);
    }

    /**
     * @param QueryBuilder $searchQueryBuilder
     * @param string[]     $emails
     */
    protected function prepareSearchCriteria(QueryBuilder $searchQueryBuilder, $emails = [])
    {
        $searchCriteria = $searchQueryBuilder->getCriteria();
        foreach ($emails as $email) {
            $searchCriteria->orWhere(
                $searchCriteria->expr()->contains('email', $email)
            );
        }
    }

    /**
     * Gets all(FROM, TO, CC, BCC) emails.
     *
     * @param Email $email
     *
     * @return string[]
     */
    protected function getEmails(Email $email)
    {
        /** @var EmailRepository $repository */
        $repository = $this->getRepository();
        $recipients = $repository->findRecipientsEmailsByEmailId($email->getId());

        $emails   = array_map(
            function ($email) {
                return $email['email'];
            },
            $recipients
        );

        $emails[] = $email->getFromEmailAddress()->getEmail();

        return array_unique($emails);
    }

    /**
     * @param Email $email
     *
     * @return array of [id, entity, title]
     */
    protected function getAssociatedEntities(Email $email)
    {
        $queryBuilder = $this->activityManager->getActivityTargetsQueryBuilder(
            $this->class,
            ['id' => $email->getId()]
        );

        return $queryBuilder->getQuery()->getArrayResult();
    }

    /**
     * Exclude assigned entities from the search(suggested to assign) result.
     *
     * @param SearchResult $searchResult
     * @param array        $queryResult
     *
     * @return array of [id, entity, title]
     */
    protected function diff(SearchResult $searchResult, array $queryResult = [])
    {
        $result = [];

        /** @var SearchResultItem $item */
        foreach ($searchResult->getElements() as $item) {
            $id        = $item->getRecordId();
            $className = $item->getEntityName();

            foreach ($queryResult as $res) {
                if ($res['id'] == $id && $res['entity'] == $className) {
                    continue 2;
                }
            }

            $result[] = [
                'id'     => $id,
                'entity' => $className,
                'title'  => $item->getRecordTitle(),
            ];
        }

        return $result;
    }
}
