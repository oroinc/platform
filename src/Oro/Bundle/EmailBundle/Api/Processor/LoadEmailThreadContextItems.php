<?php

namespace Oro\Bundle\EmailBundle\Api\Processor;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\EmailBundle\Api\Model\EmailThreadContextItem;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Component\ChainProcessor\ContextInterface;

/**
 * Loads data for the email thread context API resource.
 */
class LoadEmailThreadContextItems extends AbstractLoadEmailContextItems
{
    private ?int $emailId = null;

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ListContext $context */

        if ($context->hasResult()) {
            // result data are already retrieved
            return;
        }

        $criteria = $context->getCriteria();
        if (null === $criteria) {
            // something going wrong, it is expected that the criteria exists
            return;
        }

        $entities = $this->getRequestedEntities($context);
        $messageIds = $this->getRequestedMessageIds($context);
        $emailAddresses = $this->getRequestedEmailAddresses($context->getFilterValues());
        $excludeCurrentUser = $this->getRequestedExcludeCurrentUser($context);
        $isContext = $this->getRequestedIsContext($context);
        $searchText = $this->getRequestedSearchText($context, $emailAddresses, $excludeCurrentUser, $isContext);
        if ($context->hasErrors()) {
            return;
        }

        if ($entities) {
            if ($searchText) {
                $emailIds = $this->findEmailIdsByMessageId($messageIds);
                if ($emailIds) {
                    sort($emailIds);
                    $this->emailId = reset($emailIds);
                }
                $this->loadAndSetResultBySearchText($context, $criteria, $entities, $emailIds, $searchText);
            } else {
                [$emailIds, $existingEmailAddresses] = $this->findEmailIdsAndItsAddressesByMessageId($messageIds);
                if ($emailIds) {
                    sort($emailIds);
                    $this->emailId = reset($emailIds);
                }
                $this->loadAndSetResult(
                    $context,
                    $criteria,
                    $entities,
                    $emailIds,
                    $existingEmailAddresses,
                    $emailAddresses,
                    $excludeCurrentUser ?? false,
                    $isContext
                );
            }
        } else {
            $this->setEmptyResult($context);
        }
    }

    #[\Override]
    protected function createResultItem(
        string $id,
        string $entityClass,
        mixed $entityId,
        ?string $entityName,
        ?string $entityUrl,
        bool $isContext
    ): EmailThreadContextItem {
        return new EmailThreadContextItem($id, $entityClass, $entityId, $entityName, $entityUrl, $isContext);
    }

    #[\Override]
    protected function buildResultItemId(array $record, RequestType $requestType): string
    {
        return sprintf(
            '%s-%s-%d',
            ValueNormalizerUtil::convertToEntityType($this->valueNormalizer, $record['entity'], $requestType),
            $record['id'],
            ($record['assigned'] ?? false) && null !== $this->emailId ? $this->emailId : 0
        );
    }

    /**
     * @param string[] $messageIds
     *
     * @return int[]
     */
    private function findEmailIdsByMessageId(array $messageIds): array
    {
        $rows = $this->doctrineHelper->createQueryBuilder(Email::class, 'e')
            ->select('e.id')
            ->innerJoin(Email::class, 'p', Join::WITH, 'e.id = p.id OR e.thread = p.thread')
            ->where('p.messageId IN(:messageIds)')
            ->setParameter('messageIds', $messageIds)
            ->getQuery()
            ->getArrayResult();

        return array_column($rows, 'id');
    }

    /**
     * @param string[] $messageIds
     *
     * @return array [[email id, ...], [email address, ...]]
     */
    private function findEmailIdsAndItsAddressesByMessageId(array $messageIds): array
    {
        $rows = $this->doctrineHelper->createQueryBuilder(Email::class, 'e')
            ->select('e.id, from_addr.email AS f, to_addr.email AS t')
            ->innerJoin(Email::class, 'p', Join::WITH, 'e.id = p.id OR e.thread = p.thread')
            ->leftJoin('e.fromEmailAddress', 'from_addr')
            ->leftJoin('e.recipients', 'recipients')
            ->leftJoin('recipients.emailAddress', 'to_addr')
            ->where('p.messageId IN(:messageIds) AND recipients.type <> :bcc')
            ->setParameter('messageIds', $messageIds)
            ->setParameter('bcc', EmailRecipient::BCC)
            ->getQuery()
            ->getArrayResult();

        return $this->buildEmailIdsAndItsAddresses($rows);
    }
}
