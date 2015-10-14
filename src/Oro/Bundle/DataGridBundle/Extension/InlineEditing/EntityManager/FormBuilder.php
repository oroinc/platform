<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing\EntityManager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping\ClassMetadata;

use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Doctrine\ORM\Mapping\MappingException;

class FormBuilder
{
    protected $fieldTypeMap = [
        'string' => 'text',
        'datetime' => 'oro_datetime',
        'date' => 'oro_date'
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
     *
     * @return FormInterface
     */
    public function getForm($entity)
    {
        $form = $this->formFactory->createBuilder('form', $entity, array('csrf_protection' => false))
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
        if (!isset($data['options'])) {
            $data['options'] = [];
        }

        $form = $form->add($fieldName, $data['type'], $data['options']);

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

        if ($data !== false) {
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
     * @throws MappingException
     */
    protected function getAssociationTypeOptions(ClassMetadata $metaData, $fieldName)
    {
        $fieldInfo = $metaData->getAssociationMapping($fieldName);

        $data = [
            'type' => 'entity',
        ];

        if ($fieldInfo['type'] == 2) {
            $data['options'] = [
                'class' => $fieldInfo['targetEntity'],
                'choice_label' => $fieldInfo['joinColumns'][0]['referencedColumnName']
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
