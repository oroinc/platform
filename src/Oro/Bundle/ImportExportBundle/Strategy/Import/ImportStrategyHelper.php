<?php

namespace Oro\Bundle\ImportExportBundle\Strategy\Import;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\ImportExportBundle\Context\BatchContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Converter\ConfigurableTableDataConverter;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Authorization\AuthorizationCheckerTrait;
use Oro\Bundle\SecurityBundle\Owner\OwnerChecker;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Voter\FieldVote;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Helper methods for import strategies.
 */
class ImportStrategyHelper
{
    use AuthorizationCheckerTrait;

    /** @var ManagerRegistry */
    protected $managerRegistry;

    /** @var ValidatorInterface */
    protected $validator;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var FieldHelper */
    protected $fieldHelper;

    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /** @var ConfigurableTableDataConverter */
    protected $configurableDataConverter;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var OwnerChecker */
    protected $ownerChecker;

    /** @var array */
    protected $isGrantedCache = [];

    public function __construct(
        ManagerRegistry $managerRegistry,
        ValidatorInterface $validator,
        TranslatorInterface $translator,
        FieldHelper $fieldHelper,
        ConfigurableTableDataConverter $configurableDataConverter,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        OwnerChecker $ownerChecker
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->validator = $validator;
        $this->translator = $translator;
        $this->fieldHelper = $fieldHelper;
        $this->configurableDataConverter = $configurableDataConverter;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
        $this->ownerChecker = $ownerChecker;
    }

    public function setConfigProvider(ConfigProvider $extendConfigProvider)
    {
        $this->extendConfigProvider = $extendConfigProvider;
    }

    /**
     * @param ContextInterface $context
     * @param string $permission
     * @param object|string $entity
     * @param string $entityName
     * @return null
     */
    public function checkPermissionGrantedForEntity(ContextInterface $context, $permission, $entity, $entityName)
    {
        if (!$this->isGranted($permission, $entity)) {
            $error = $this->translator->trans(
                'oro.importexport.import.errors.access_denied_entity',
                ['%entity_name%' => $entityName,]
            );
            $context->addError($error);

            return false;
        }

        return true;
    }

    /**
     * @param ContextInterface $context
     * @param object $entity
     * @param bool $suppressErrorOutput
     * @return bool
     */
    public function checkEntityOwnerPermissions(ContextInterface $context, $entity, bool $suppressErrorOutput = false)
    {
        if (!$this->ownerChecker->isOwnerCanBeSet($entity)) {
            if (!$suppressErrorOutput) {
                $error = $this->translator->trans(
                    'oro.importexport.import.errors.wrong_owner'
                );
                $this->addValidationErrors([$error], $context);
            }

            return false;
        }

        return true;
    }

    /**
     * @param ContextInterface $context
     * @param object $entity
     * @param object|null $existingEntity
     * @param array|null $itemData
     *
     * @return bool
     */
    public function checkImportedEntityFieldsAcl(
        ContextInterface $context,
        $entity,
        $existingEntity = null,
        $itemData = null
    ): bool {
        $entityName = ClassUtils::getClass($entity);
        $fields = $this->fieldHelper->getEntityFields($entityName, EntityFieldProvider::OPTION_WITH_RELATIONS);
        $action = $existingEntity ? 'EDIT' : 'CREATE';
        $checkEntity = $existingEntity ?: new ObjectIdentity('entity', $entityName);
        $isValid = true;

        foreach ($fields as $field) {
            if ($itemData && !array_key_exists($field['name'], (array) $itemData)) {
                // Skips ACL check because field is not present in import.
                continue;
            }

            if (!$this->isGranted($action, $checkEntity, $field['name'])) {
                $error = $this->translator->trans(
                    'oro.importexport.import.errors.access_denied_property_entity',
                    [
                        '%property_name%' => $field['name'],
                        '%entity_name%' => $entityName,
                    ]
                );
                $isValid = false;
                $context->addError($error);
                if ($existingEntity) {
                    $existingValue = $this->fieldHelper->getObjectValue($existingEntity, $field['name']);
                    $this->fieldHelper->setObjectValue($entity, $field['name'], $existingValue);
                } else {
                    $this->fieldHelper->setObjectValue($entity, $field['name'], null);
                }
            }
        }

        return $isValid;
    }

    /**
     * Checks if an access to a resource is granted to the caller
     *
     * @param string|string[] $attributes Can be a role name(s), permission name(s), an ACL annotation id,
     *                                    string in format "permission;descriptor"
     *                                    (VIEW;entity:AcmeDemoBundle:AcmeEntity, EDIT;action:acme_action)
     *                                    or something else, it depends on registered security voters
     * @param  object|string $obj        A domain object, object identity or object identity descriptor
     *
     * @param  string         $property
     * @return bool
     */
    public function isGranted($attributes, $obj, $property = null)
    {
        if (!$this->tokenAccessor->hasUser()) {
            return true;
        }

        $cacheKey = $this->getIsGrantedCacheKey($attributes, $obj, $property);
        if (array_key_exists($cacheKey, $this->isGrantedCache)) {
            return $this->isGrantedCache[$cacheKey];
        }

        if ($property && !($obj instanceof FieldVote)) {
            $obj = new FieldVote($obj, $property);
        }

        try {
            $this->isGrantedCache[$cacheKey] = $this->isAttributesGranted(
                $this->authorizationChecker,
                $attributes,
                $obj
            );
        } catch (InvalidDomainObjectException $exception) {
            // if object do not have identity we skip check
            $this->isGrantedCache[$cacheKey] = true;
        }

        return $this->isGrantedCache[$cacheKey];
    }

    /**
     * @param string|string[] $attributes
     * @param object|string $obj
     * @param string|null $property
     *
     * @return string
     */
    private function getIsGrantedCacheKey($attributes, $obj, $property = null): string
    {
        if ($obj instanceof ObjectIdentityInterface) {
            $oid = sprintf('%s:%s', $obj->getIdentifier(), $obj->getType());
        } else {
            $oid = is_object($obj) ? \spl_object_hash($obj) : $obj;
        }

        return sprintf('%s:%s:%s', $oid, implode('_', (array) $attributes), (string) $property);
    }

    /**
     * @param string $entityClass
     * @return EntityManager
     * @throws LogicException
     */
    public function getEntityManager($entityClass)
    {
        $entityManager = $this->managerRegistry->getManagerForClass($entityClass);
        if (!$entityManager) {
            throw new LogicException(
                sprintf('Can\'t find entity manager for %s', $entityClass)
            );
        }

        return $entityManager;
    }

    /**
     * @param object $basicEntity
     * @param object $importedEntity
     * @param array $excludedProperties
     * @throws InvalidArgumentException
     */
    public function importEntity($basicEntity, $importedEntity, array $excludedProperties = [])
    {
        $basicEntityClass = $this->verifyClass($basicEntity, $importedEntity);

        $entityProperties = $this->getEntityPropertiesByClassName($basicEntityClass);
        $importedEntityProperties = array_diff($entityProperties, $excludedProperties);

        foreach ($importedEntityProperties as $propertyName) {
            // we should not overwrite deleted fields
            if ($this->isDeletedField($basicEntityClass, $propertyName)) {
                continue;
            }

            $importedValue = $this->fieldHelper->getObjectValue($importedEntity, $propertyName);
            $this->fieldHelper->setObjectValue($basicEntity, $propertyName, $importedValue);
        }
    }

    /**
     * Validate entity, returns list of errors or null
     *
     * @param object $entity
     * @param Constraint|Constraint[]|null $constraints
     * @param array|null $groups
     *
     * @return array|null
     */
    public function validateEntity($entity, $constraints = null, $groups = null)
    {
        $violations = $this->validator->validate($entity, $constraints, $groups);
        if (count($violations)) {
            $errors = [];

            /** @var ConstraintViolationInterface $violation */
            foreach ($violations as $violation) {
                $propertyPath = $violation->getPropertyPath();
                if ($propertyPath && is_object($entity)) {
                    $fieldHeader = $this->configurableDataConverter->getFieldHeaderWithRelation(
                        ClassUtils::getClass($entity),
                        $propertyPath
                    );
                    $propertyPath = ($fieldHeader ?: $propertyPath) . ': ';
                }
                $errors[] = $propertyPath . $violation->getMessage();
            }
            return $errors;
        }

        return null;
    }

    /**
     * @param array $validationErrors
     * @param ContextInterface $context
     * @param string|null $errorPrefix
     */
    public function addValidationErrors(array $validationErrors, ContextInterface $context, $errorPrefix = null)
    {
        if (null === $errorPrefix) {
            $errorPrefix = $this->translator->trans(
                'oro.importexport.import.error %number%',
                [
                    '%number%' => $this->getCurrentRowNumber($context),
                ]
            );
        }
        foreach ($validationErrors as $validationError) {
            $context->addError($errorPrefix . ' ' . $validationError);
        }
    }

    /**
     * @return AbstractUser|null
     */
    public function getLoggedUser()
    {
        return $this->tokenAccessor->getUser();
    }

    /**
     * Check if given class field is deleted
     *
     * @param string $className FQCN
     * @param string $fieldName
     *
     * @return bool
     */
    protected function isDeletedField($className, $fieldName)
    {
        if ($this->extendConfigProvider->hasConfig($className, $fieldName)) {
            return $this->extendConfigProvider->getConfig($className, $fieldName)->is('is_deleted');
        }

        return false;
    }

    /**
     * @param string $entityClassName
     *
     * @return array
     */
    protected function getEntityPropertiesByClassName($entityClassName)
    {
        /**
         * In case if we work with configured entities then we should use fieldHelper
         * to getting fields because it won't returns any hidden fields (f.e snapshot fields)
         * that mustn't be changed by import/export
         */
        if ($this->extendConfigProvider->hasConfig($entityClassName)) {
            $properties = $this->fieldHelper->getEntityFields(
                $entityClassName,
                EntityFieldProvider::OPTION_WITH_RELATIONS
            );

            return array_column($properties, 'name');
        }

        $entityMetadata = $this
            ->getEntityManager($entityClassName)
            ->getClassMetadata($entityClassName);

        return array_merge(
            $entityMetadata->getFieldNames(),
            $entityMetadata->getAssociationNames()
        );
    }

    public function getCurrentRowNumber(ContextInterface $context): int
    {
        $batchSize = null;
        $batchNumber = null;
        if ($context instanceof BatchContextInterface) {
            $batchSize = (int) $context->getBatchSize();
            $batchNumber = (int) $context->getBatchNumber();
        }

        $rowNumber = (int) $context->getReadOffset();
        if ($batchNumber && $batchSize) {
            $rowNumber += --$batchNumber * $batchSize;
        }

        return $rowNumber;
    }

    protected function verifyClass($basicEntity, $importedEntity): string
    {
        $basicEntityClass = ClassUtils::getClass($basicEntity);
        if ($basicEntityClass !== ClassUtils::getClass($importedEntity)) {
            throw new InvalidArgumentException('Basic and imported entities must be instances of the same class');
        }

        return $basicEntityClass;
    }
}
