<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\Writer;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;

use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache;

class EntityFieldWriter implements ItemWriterInterface, StepExecutionAwareInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var DynamicTranslationMetadataCache
     */
    protected $dbTranslationMetadataCache;

    /** @var StepExecution */
    protected $stepExecution;

    /**
     * @param ManagerRegistry                 $doctrine
     * @param ConfigManager                   $configManager
     * @param Translator                      $translator
     * @param DynamicTranslationMetadataCache $dbTranslationMetadataCache
     */
    public function __construct(
        ManagerRegistry $doctrine,
        ConfigManager $configManager,
        Translator $translator,
        DynamicTranslationMetadataCache $dbTranslationMetadataCache
    ) {
        $this->doctrine                   = $doctrine;
        $this->configManager              = $configManager;
        $this->translator                 = $translator;
        $this->dbTranslationMetadataCache = $dbTranslationMetadataCache;
    }

    /**
     * @param StepExecution $stepExecution
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        foreach ($items as $item) {
            $this->writeItem($item);
        }
    }

    /**
     * @param FieldConfigModel $configModel
     */
    protected function writeItem(FieldConfigModel $configModel)
    {
        $labelsToBeUpdated = [];
        foreach ($this->configManager->getProviders() as $provider) {
            $scope = $provider->getScope();

            $data = $configModel->toArray($scope);

            if (!empty($data)) {
                $configId = $this->configManager->getConfigIdByModel($configModel, $scope);
                $config   = $provider->getConfigById($configId);

                $translatable = $provider->getPropertyConfig()->getTranslatableValues($configId);
                foreach ($data as $code => $value) {
                    if (in_array($code, $translatable, true)) {
                        // check if a label text was changed
                        $labelKey = $config->get($code);
                        if (!$configModel->getId()) {
                            $labelsToBeUpdated[$labelKey] = $value;
                        } elseif ($value != $this->translator->trans($labelKey)) {
                            $labelsToBeUpdated[$labelKey] = $value;
                        }
                        // replace label text with label name in $value variable
                        $value = $config->get($code);
                    }
                    $config->set($code, $value);
                }

                $this->configManager->persist($config);
            }
        }

        // update changed labels if any
        if (!empty($labelsToBeUpdated)) {
            /** @var EntityManager $translationEm */
            $translationEm = $this->doctrine->getManagerForClass(Translation::ENTITY_NAME);
            /** @var TranslationRepository $translationRepo */
            $translationRepo = $translationEm->getRepository(Translation::ENTITY_NAME);

            $values = [];
            $locale = $this->translator->getLocale();
            foreach ($labelsToBeUpdated as $labelKey => $labelText) {
                // save into translation table
                $values[] = $translationRepo->saveValue(
                    $labelKey,
                    $labelText,
                    $locale,
                    TranslationRepository::DEFAULT_DOMAIN,
                    Translation::SCOPE_UI
                );
            }
            // mark translation cache dirty
            $this->dbTranslationMetadataCache->updateTimestamp($locale);

            $translationEm->flush($values);
        }

        $this->configManager->flush();
    }
}
