<?php

namespace Oro\Bundle\WorkflowBundle\Datagrid;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class WorkflowDefinitionGridEntityNameProvider
{
    /**
     * @var array
     */
    protected $relatedEntities = array();

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var ConfigProviderInterface
     */
    protected $configProvider;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param ConfigProviderInterface $configProvider
     * @param EntityManager $em
     * @param TranslatorInterface $translator
     */
    public function __construct(
        ConfigProviderInterface $configProvider,
        EntityManager $em,
        TranslatorInterface $translator
    ) {
        $this->configProvider = $configProvider;
        $this->em = $em;
        $this->translator = $translator;
    }

    /**
     * Get workflow definition related entities.
     *
     * @return array
     */
    public function getRelatedEntitiesChoice()
    {
        if (empty($this->relatedEntities)) {
            $qb = $this->em->createQueryBuilder();
            $qb->select('w.relatedEntity')
                ->from('OroWorkflowBundle:WorkflowDefinition', 'w')
                ->distinct('w.relatedEntity');

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
}
