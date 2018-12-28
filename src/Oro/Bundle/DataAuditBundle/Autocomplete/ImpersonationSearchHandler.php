<?php

namespace Oro\Bundle\DataAuditBundle\Autocomplete;

use Oro\Bundle\DataAuditBundle\Entity\AbstractAudit;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\UserBundle\Entity\Impersonation;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Autocomplete Search Handler for Impersonation used in Audit Logs
 */
class ImpersonationSearchHandler implements SearchHandlerInterface
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param TranslatorInterface $translator
     */
    public function __construct(DoctrineHelper $doctrineHelper, TranslatorInterface $translator)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function convertItem($item)
    {
        if (!$item instanceof Impersonation) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Expected argument of type "%s", "%s" given',
                    Impersonation::class,
                    is_object($item) ? get_class($item) : gettype($item)
                )
            );
        }

        $token = $item->getToken();
        if (!$token) {
            throw new \InvalidArgumentException(
                sprintf('Expected Impersonation contains token')
            );
        }

        $ipAddress = $item->getIpAddress();
        if (!$ipAddress) {
            throw new \InvalidArgumentException(
                sprintf('Expected Impersonation contains ipAddress')
            );
        }

        return [
            'id' => $item->getId(),
            'ipAddress' => $ipAddress,
            'token' => $token,
            'ipAddressToken' => $this->translator->trans(
                'oro.dataaudit.datagrid.author_impersonation_filter',
                ['%ipAddress%' => $ipAddress, '%token%' => $token]
            ),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function search($query, $page, $perPage, $searchById = false)
    {
        $impersonationQB = $this->doctrineHelper->getEntityRepository($this->getEntityName())->createQueryBuilder('i');

        if ($searchById) {
            $impersonationQB
                ->andWhere(
                    $impersonationQB->expr()->in('i.id', ':ids')
                )
                ->setParameter('ids', array_filter(explode(',', $query)));
        } else {
            $impersonationQB
                ->andWhere(
                    $impersonationQB->expr()->orX(
                        $impersonationQB->expr()->like('i.ipAddress', ':query'),
                        $impersonationQB->expr()->like('i.token', ':query')
                    )
                )
                ->setParameter('query', '%'.trim($query).'%');
        }

        $auditQB = $this->doctrineHelper->getEntityRepository(AbstractAudit::class)->createQueryBuilder('a');
        $auditQB
            ->select('IDENTITY(a.impersonation)');

        $impersonationQB
            ->andWhere($impersonationQB->expr()->in('i.id', $auditQB->getDQL()))
            ->setFirstResult(--$page * $perPage)
            ->orderBy($impersonationQB->expr()->desc('i.id'))
            ->setMaxResults($perPage);

        $result = $impersonationQB->getQuery()->getResult();

        $resultsData = [];
        foreach ($result as $impersonation) {
            $resultsData[] = $this->convertItem($impersonation);
        }

        return [
            'results' => array_slice($resultsData, 0, $perPage),
            'more' => (bool)array_slice($resultsData, $perPage),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties()
    {
        return ['ipAddress', 'token', 'ipAddressToken'];
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityName()
    {
        return Impersonation::class;
    }
}
