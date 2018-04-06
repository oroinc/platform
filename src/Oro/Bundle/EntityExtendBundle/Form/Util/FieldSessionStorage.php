<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Util;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Symfony\Component\HttpFoundation\Session\Session;

class FieldSessionStorage
{
    const SESSION_ID_FIELD_NAME = '_extendbundle_create_entity_%s_field_name';
    const SESSION_ID_FIELD_TYPE = '_extendbundle_create_entity_%s_field_type';

    /**
     * @var Session
     */
    private $session;

    /**
     * FieldSessionStorage constructor.
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * @param EntityConfigModel $entityConfigModel
     * @return array
     */
    public function getFieldInfo(EntityConfigModel $entityConfigModel)
    {
        $fieldName = $this->session->get(
            sprintf(self::SESSION_ID_FIELD_NAME, $entityConfigModel->getId())
        );

        $fieldType = $this->session->get(
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

    /**
     * @param EntityConfigModel $entityConfigModel
     * @param $fieldName
     * @param $fieldType
     */
    public function saveFieldInfo(EntityConfigModel $entityConfigModel, $fieldName, $fieldType)
    {
        $this->session->set(
            sprintf(self::SESSION_ID_FIELD_NAME, $entityConfigModel->getId()),
            $fieldName
        );

        $this->session->set(
            sprintf(self::SESSION_ID_FIELD_TYPE, $entityConfigModel->getId()),
            $fieldType
        );
    }
}
