<?php

namespace Oro\Bundle\WorkflowBundle\Datagrid;

use Symfony\Component\Translation\TranslatorInterface;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\WorkflowBundle\Exception\MissedRequiredOptionException;

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
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param ConfigProvider      $configProvider
     * @param EntityManager       $entityManager
     * @param TranslatorInterface $translator
     */
    public function __construct(
        ConfigProvider $configProvider,
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
        if (!$this->entityName) {
            throw new MissedRequiredOptionException('Entity name is required.');
        }

        if (empty($this->relatedEntities)) {
            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('entity.relatedEntity')
                ->from($this->entityName, 'entity')
                ->distinct('entity.relatedEntity');

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

    /**
     * @param string $tableName
     */
    public function setEntityName($tableName)
    {
        $this->entityName = $tableName;
    }
}
