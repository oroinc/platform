<?php

namespace Oro\Bundle\WorkflowBundle\Datagrid;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\WorkflowBundle\Exception\MissedRequiredOptionException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides workflow definition related entities.
 */
class GridEntityNameProvider
{
    private array $relatedEntities = [];
    private ?string $entityName = null;

    public function __construct(
        private ConfigProvider $configProvider,
        private ManagerRegistry $doctrine,
        private TranslatorInterface $translator
    ) {
    }

    /**
     * Gets workflow definition related entities.
     *
     * @throws MissedRequiredOptionException
     */
    public function getRelatedEntitiesChoice(): array
    {
        if (!$this->entityName) {
            throw new MissedRequiredOptionException('Entity name is required.');
        }

        if (empty($this->relatedEntities)) {
            /** @var QueryBuilder $qb */
            $qb = $this->doctrine->getManager()->createQueryBuilder();
            $qb->select('entity.relatedEntity')
                ->from($this->entityName, 'entity')
                ->distinct('entity.relatedEntity');

            $result = (array)$qb->getQuery()->getArrayResult();
            foreach ($result as $value) {
                $className = $value['relatedEntity'];
                $label = $className;
                if ($this->configProvider->hasConfig($className)) {
                    $config = $this->configProvider->getConfig($className);
                    $label = $this->translator->trans((string) $config->get('label'));
                }

                $this->relatedEntities[$label] = $className;
            }
        }

        return $this->relatedEntities;
    }

    public function setEntityName(string $tableName): void
    {
        $this->entityName = $tableName;
    }
}
