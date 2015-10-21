<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\Strategy;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;

class EntityFieldImportStrategy extends ConfigurableAddOrReplaceStrategy
{
    const PROCESSED_ENTITIES_HASH = 'processedEntitiesHash';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     * @return EntityFieldImportStrategy
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;

        return $this;
    }

    /**
     * @param FieldConfigModel $entity
     * @return FieldConfigModel|null
     *
     * {@inheritdoc}
     */
    protected function validateAndUpdateContext($entity)
    {
        $validatedEntity = parent::validateAndUpdateContext($entity);

        if (null !== $validatedEntity) {
            $processedEntities = (array)$this->context->getValue(self::PROCESSED_ENTITIES_HASH);
            $hash = $this->getEntityHashByUniqueFields($entity);

            if (!empty($processedEntities[$hash])) {
                $validatedEntity = null;
            } else {
                $processedEntities[$hash] = true;
                $this->context->setValue(self::PROCESSED_ENTITIES_HASH, $processedEntities);
            }
        }

        return $validatedEntity;
    }

    /**
     * @param FieldConfigModel $entity
     * @return string
     */
    protected function getEntityHashByUniqueFields(FieldConfigModel $entity)
    {
        return md5(
            implode(
                ':',
                [
                    $entity->getFieldName(),
                    $entity->getEntity() ? $entity->getEntity()->getClassName() : null,
                ]
            )
        );
    }
}
