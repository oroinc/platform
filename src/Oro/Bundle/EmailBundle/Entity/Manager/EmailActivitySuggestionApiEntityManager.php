<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Oro\Bundle\SearchBundle\Query\Result as SearchResult;
use Oro\Bundle\SearchBundle\Query\Result\Item as SearchResultItem;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailRepository;

class EmailActivitySuggestionApiEntityManager extends EmailActivitySearchApiEntityManager
{
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
            $this->getAssignedEntities($email)
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
        $filters = [
            'search' => false,
            'emails' => $this->getEmails($email),
        ];

        // Prepare search query builder without limits and offsets
        $searchQueryBuilder = parent::getListQueryBuilder(0, 0, $filters);

        return $this->searchIndexer->query($searchQueryBuilder);
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
    protected function getAssignedEntities(Email $email)
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
     * @param array        $assignedEntities
     *
     * @return array of [id, entity, title]
     */
    protected function diff(SearchResult $searchResult, array $assignedEntities = [])
    {
        $result = [];

        /** @var SearchResultItem $item */
        foreach ($searchResult->getElements() as $item) {
            $id        = $item->getRecordId();
            $className = $item->getEntityName();

            foreach ($assignedEntities as $entity) {
                if ($entity['id'] == $id && $entity['entity'] == $className) {
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
