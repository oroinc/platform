<?php

namespace Oro\Bundle\WorkflowBundle\Datagrid;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;
use Oro\Bundle\WorkflowBundle\Exception\MissedRequiredOptionException;

use Symfony\Component\Translation\TranslatorInterface;

class GridEntityNameProvider
{
    /**
     * @var EntityManager
     */
    protected $entityManager;
    /**
     * @var array
     */
    protected $relatedEntities = array();

    /**
     * @var ConfigProviderInterface
     */
    protected $configProvider;

    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param ConfigProviderInterface $configProvider
     * @param EntityManager $entityManager
     * @param TranslatorInterface $translator
     */
    public function __construct(
        ConfigProviderInterface $configProvider,
        EntityManager $entityManager,
        TranslatorInterface $translator
    ) {
        $this->configProvider = $configProvider;
        $this->entityManager = $entityManager;
        $this->translator = $translator;
    }

    /**
     * Get workflow definition related entities.
     *
     * @throws MissedRequiredOptionException
     * @return array
     */
    public function getRelatedEntitiesChoice()
    {
        if (!$this->tableName) {
            throw new MissedRequiredOptionException('Parameter "tableName" is required.');
        }

        if (empty($this->relatedEntities)) {
            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('table.relatedEntity')
                ->from($this->tableName, 'table')
                ->distinct('table.relatedEntity');

            $result = (array)$qb->getQuery()->getArrayResult();

            foreach ($result as $value) {
                $className = $value['relatedEntity'];
                $label = $className;
                if ($this->configProvider->hasConfig($className)) {
                    $config = $this->configProvider->getConfig($className);
                    $label = $this->translator->trans($config->get('label'));
                }

                $this->relatedEntities[$className] = $label;
            }
        }

        return $this->relatedEntities;
    }

    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
    }
}
