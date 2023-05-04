<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Util;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Storage of fields session.
 */
class FieldSessionStorage
{
    const SESSION_ID_FIELD_NAME = '_extendbundle_create_entity_%s_field_name';
    const SESSION_ID_FIELD_TYPE = '_extendbundle_create_entity_%s_field_type';

    /**
     * FieldSessionStorage constructor.
     */
    public function __construct(private RequestStack $requestStack)
    {
    }

    /**
     * @param EntityConfigModel $entityConfigModel
     * @return array
     */
    public function getFieldInfo(EntityConfigModel $entityConfigModel)
    {
        $session = $this->requestStack->getSession();
        $fieldName = $session->get(
            sprintf(self::SESSION_ID_FIELD_NAME, $entityConfigModel->getId())
        );

        $fieldType = $session->get(
            sprintf(self::SESSION_ID_FIELD_TYPE, $entityConfigModel->getId())
        );

        return [$fieldName, $fieldType];
    }

    /**
     * @param EntityConfigModel $entityConfigModel
     * @return bool
     */
    public function hasFieldInfo(EntityConfigModel $entityConfigModel)
    {
        list($fieldName, $fieldType) = $this->getFieldInfo($entityConfigModel);

        return $fieldName && $fieldType;
    }

    public function saveFieldInfo(EntityConfigModel $entityConfigModel, $fieldName, $fieldType)
    {
        $session = $this->requestStack->getSession();
        $session->set(
            sprintf(self::SESSION_ID_FIELD_NAME, $entityConfigModel->getId()),
            $fieldName
        );

        $session->set(
            sprintf(self::SESSION_ID_FIELD_TYPE, $entityConfigModel->getId()),
            $fieldType
        );
    }
}
