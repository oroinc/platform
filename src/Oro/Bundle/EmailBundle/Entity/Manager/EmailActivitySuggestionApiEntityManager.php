<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\SearchBundle\Query\Result as SearchResult;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

class EmailActivitySuggestionApiEntityManager extends EmailActivitySearchApiEntityManager
{
    /** @var TokenStorageInterface|null */
    protected $tokenStorage;

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function setTokenStorage(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function find($id)
    {
        // force load from and to email addresses by one query
        $object = $this->getRepository()->createQueryBuilder('e')
            ->select('e, from_addr, recipients, to_addr')
            ->leftJoin('e.fromEmailAddress', 'from_addr')
            ->leftJoin('e.recipients', 'recipients')
            ->leftJoin('recipients.emailAddress', 'to_addr')
            ->where('e.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        if ($object) {
            $this->checkFoundEntity($object);
        }

        return $object;
    }

    /**
     * Gets suggestion result.
     *
     * @param int  $emailId
     * @param int  $page
     * @param int  $limit
     * @param bool $excludeCurrentUser
     *
     * @return array
     */
    public function getSuggestionResult(
        $emailId,
        $page = 1,
        $limit = 10,
        $excludeCurrentUser = false
    ) {
        /** @var Email $email */
        $email = $this->find($emailId);
        if (!$email) {
            throw new NotFoundHttpException();
        }

        $excludeEntities = [];
        if ($excludeCurrentUser && $this->tokenStorage) {
            $token = $this->tokenStorage->getToken();
            if ($token && $token->getUser() instanceof AbstractUser) {
                $currentUser = $token->getUser();
                $excludeEntities[] = ['entity' => ClassUtils::getClass($currentUser), 'id' => $currentUser->getId()];
            }
        }

        $data = $this->diff(
            $this->getSuggestionEntities($email),
            $this->getAssignedEntities($email),
            $excludeEntities
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
     * @return array of [id, entity, title]
     */
    protected function getSuggestionEntities(Email $email)
    {
        $entities = [];

        $filters = [
            'search' => false,
            'emails' => $this->getEmails($email),
        ];

        // Prepare search query builder without limits and offsets
        $searchQueryBuilder = parent::getListQueryBuilder(0, 0, $filters);

        $searchResult = $this->searchIndexer->query($searchQueryBuilder);

        if ($searchResult->count() > 0) {
            $entities = $this->getEmailAssociatedEntitiesQueryBuilder($searchResult)->getQuery()->getResult();
        }

        return $entities;
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
        $emails = [];
        foreach ($email->getRecipients() as $recipient) {
            $emails[] = $recipient->getEmailAddress()->getEmail();
        }
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
     * @param array $searchResult
     * @param array $assignedEntities
     * @param array $excludeEntities  The list of entities that should be excluded from the result
     *                                [['entity' => entity class, 'id' => entity id], ...]
     *
     * @return array of [id, entity, title]
     */
    protected function diff($searchResult, array $assignedEntities = [], array $excludeEntities = [])
    {
        $result = [];

        $assignedEntities = array_merge($assignedEntities, $excludeEntities);

        foreach ($searchResult as $item) {
            foreach ($assignedEntities as $entity) {
                if ($entity['id'] == $item['id'] && $entity['entity'] == $item['entity']) {
                    continue 2;
                }
            }
            $result[] = $item;
        }

        return $result;
    }
}
