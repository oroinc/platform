<?php

namespace Oro\Bundle\EntityBundle\Form\EntityField;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Mapping\MappingException;

use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;

class FormBuilder
{
    protected $fieldTypeMap = [
        'string' => 'text',
        'datetime' => 'oro_datetime',
        'date' => 'oro_date',
        'boolean' => 'checkbox',
        'float' => 'number',
        'decimal' => 'number',
    ];

    /** @var FormFactory */
    protected $formFactory;

    /** @var Registry */
    protected $registry;

    /**
     * @param FormFactory $formFactory
     * @param Registry $registry
     */
    public function __construct(
        FormFactory $formFactory,
        Registry $registry
    ) {
        $this->formFactory = $formFactory;
        $this->registry = $registry;
    }

    /**
     * @param $entity
     * @param $fields
     *
     * @return FormInterface
     */
    public function build($entity, $fields)
    {
        $form = $this->getForm($entity);
        $keys = array_keys($fields);
        foreach ($keys as $fieldName) {
            $form = $this->add($form, $entity, $fieldName);
        }

        return $form;
    }

    /**
     * @param $entity
     *
     * @return FormInterface
     */
    public function getForm($entity)
    {
        $form = $this->formFactory
            ->createBuilder(
                'form',
                $entity,
                [
                    'csrf_protection' => false,
                    'dynamic_fields_disabled' => true
                ]
            )
            ->getForm();

        return $form;
    }

    /**
     * @param FormInterface $form
     * @param $entity
     * @param $fieldName
     *
     * @return FormInterface
     */
    public function add(FormInterface $form, $entity, $fieldName)
    {
        $data = $this->getAssociationType($entity, $fieldName);
        if (is_array($data)) {
            if (!array_key_exists('options', $data)) {
                $data['options'] = [];
            }

            $form = $form->add($fieldName, $data['type'], $data['options']);
        }

        return $form;
    }

    /**
     * @param $entity
     * @param $fieldName
     *
     * @return array|bool
     */
    protected function getAssociationType($entity, $fieldName)
    {
        $data = false;
        $metaData = $this->getMetaData($entity);

        if ($metaData->hasField($fieldName)) {
            $data = $this->getSimpleTypeOptions($metaData, $fieldName);
        }

        if ($metaData->hasAssociation($fieldName)) {
            $data = $this->getAssociationTypeOptions($metaData, $fieldName);
        }

        if (is_array($data)) {
            $currentType = $data['type'];
            if (array_key_exists($currentType, $this->fieldTypeMap)) {
                $data['type'] = $this->fieldTypeMap[$currentType];
            }
        }

        return $data;
    }

    /**
     * @param ClassMetadata $metaData
     * @param $fieldName
     *
     * @return array
     *
     * @throws MappingException
     */
    protected function getSimpleTypeOptions(ClassMetadata $metaData, $fieldName)
    {
        $fieldInfo = $metaData->getFieldMapping($fieldName);

        $data = [
            'type' => $fieldInfo['type']
        ];
        return $data;
    }

    /**
     * @param ClassMetadata $metaData
     * @param $fieldName
     *
     * @return array
     *
     * @throws MappingException
     */
    protected function getAssociationTypeOptions(ClassMetadata $metaData, $fieldName)
    {
        $fieldInfo = $metaData->getAssociationMapping($fieldName);

        $data = [
            'type' => 'entity',
        ];

        if ($fieldInfo['type'] === ClassMetadataInfo::MANY_TO_ONE
            || $fieldInfo['type'] === ClassMetadataInfo::ONE_TO_ONE) {
            $data['options'] = [
                'class' => $fieldInfo['targetEntity'],
                'choice_label' => $fieldInfo['joinColumns'][0]['referencedColumnName']
            ];
        }

        if ($fieldInfo['type'] === ClassMetadataInfo::MANY_TO_MANY) {
            $data['options'] = [
                'class' => $fieldInfo['targetEntity'],
                'choice_label' => $fieldInfo['joinTable']['joinColumns'][0]['referencedColumnName'],
                'multiple' => true
            ];
        }

        return $data;
    }

    /**
     * @param $entity
     *
     * @return ClassMetadata
     */
    protected function getMetaData($entity)
    {
        $className = ClassUtils::getClass($entity);
        $em = $this->registry->getManager();

        return $em->getClassMetadata($className);
    }
}
