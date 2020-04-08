- [ApiBundle](#apibundle)
- [ChainProcessor](#chainprocessor)
- [EmailBundle](#emailbundle)
- [EntityConfigBundle](#entityconfigbundle)
- [EntityExtendBundle](#entityextendbundle)
- [LocaleBundle](#localebundle)
- [SecurityBundle](#securitybundle)
- [Testing](#testing)

ApiBundle
---------
* The `ByStepNormalizeResultContext::setFailedGroup($groupName)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/ApiBundle/Processor/ByStepNormalizeResultContext.php#L29 "Oro\Bundle\ApiBundle\Processor\ByStepNormalizeResultContext")</sup> method was changed to `ByStepNormalizeResultContext::setFailedGroup($groupName)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha/src/Oro/Bundle/ApiBundle/Processor/ByStepNormalizeResultContext.php#L61 "Oro\Bundle\ApiBundle\Processor\ByStepNormalizeResultContext")</sup>
* The `ApiDocDataTypeConverter::__construct(array $map)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/ApiBundle/ApiDoc/ApiDocDataTypeConverter.php#L16 "Oro\Bundle\ApiBundle\ApiDoc\ApiDocDataTypeConverter")</sup> method was changed to `ApiDocDataTypeConverter::__construct(array $defaultMapping, array $viewMappings)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha/src/Oro/Bundle/ApiBundle/ApiDoc/ApiDocDataTypeConverter.php#L20 "Oro\Bundle\ApiBundle\ApiDoc\ApiDocDataTypeConverter")</sup>
* The `ApiDocMetadataParser::__construct(ValueNormalizer $valueNormalizer, ApiDocDataTypeConverter $dataTypeConverter)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/ApiBundle/ApiDoc/Parser/ApiDocMetadataParser.php#L35 "Oro\Bundle\ApiBundle\ApiDoc\Parser\ApiDocMetadataParser")</sup> method was changed to `ApiDocMetadataParser::__construct(ValueNormalizer $valueNormalizer, RestDocViewDetector $docViewDetector, ApiDocDataTypeConverter $dataTypeConverter)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha/src/Oro/Bundle/ApiBundle/ApiDoc/Parser/ApiDocMetadataParser.php#L40 "Oro\Bundle\ApiBundle\ApiDoc\Parser\ApiDocMetadataParser")</sup>
* The `MetadataTypeGuesser::addDataTypeMapping`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/ApiBundle/Form/Guesser/MetadataTypeGuesser.php#L128 "Oro\Bundle\ApiBundle\Form\Guesser\MetadataTypeGuesser::addDataTypeMapping")</sup> method was removed.
* The `ApiDocDataTypeConverter::addDataType`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/ApiBundle/ApiDoc/ApiDocDataTypeConverter.php#L25 "Oro\Bundle\ApiBundle\ApiDoc\ApiDocDataTypeConverter::addDataType")</sup> method was removed.

ChainProcessor
--------------
* The `ContextInterface::resetSkippedGroups`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha/src/Oro/Component/ChainProcessor/ContextInterface.php#L102 "Oro\Component\ChainProcessor\ContextInterface::resetSkippedGroups")</sup> interface method was added.

EmailBundle
-----------
* The `SendEmailTemplate::validateAddress`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/EmailBundle/Workflow/Action/SendEmailTemplate.php#L188 "Oro\Bundle\EmailBundle\Workflow\Action\SendEmailTemplate::validateAddress")</sup> method was removed.

EntityConfigBundle
------------------
* The `ConfigProvider::getClassName`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/EntityConfigBundle/Provider/ConfigProvider.php#L232 "Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider::getClassName")</sup> method was removed.
* The following methods in class `OneToManyAttributeType`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/EntityConfigBundle/Attribute/Type/OneToManyAttributeType.php#L23 "Oro\Bundle\EntityConfigBundle\Attribute\Type\OneToManyAttributeType")</sup> were removed:
   - `__construct`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/EntityConfigBundle/Attribute/Type/OneToManyAttributeType.php#L23 "Oro\Bundle\EntityConfigBundle\Attribute\Type\OneToManyAttributeType::__construct")</sup>
   - `isSearchable`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/EntityConfigBundle/Attribute/Type/OneToManyAttributeType.php#L31 "Oro\Bundle\EntityConfigBundle\Attribute\Type\OneToManyAttributeType::isSearchable")</sup>
   - `isFilterable`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/EntityConfigBundle/Attribute/Type/OneToManyAttributeType.php#L39 "Oro\Bundle\EntityConfigBundle\Attribute\Type\OneToManyAttributeType::isFilterable")</sup>
   - `isSortable`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/EntityConfigBundle/Attribute/Type/OneToManyAttributeType.php#L47 "Oro\Bundle\EntityConfigBundle\Attribute\Type\OneToManyAttributeType::isSortable")</sup>
   - `getSearchableValue`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/EntityConfigBundle/Attribute/Type/OneToManyAttributeType.php#L55 "Oro\Bundle\EntityConfigBundle\Attribute\Type\OneToManyAttributeType::getSearchableValue")</sup>
   - `getFilterableValue`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/EntityConfigBundle/Attribute/Type/OneToManyAttributeType.php#L63 "Oro\Bundle\EntityConfigBundle\Attribute\Type\OneToManyAttributeType::getFilterableValue")</sup>
   - `getSortableValue`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/EntityConfigBundle/Attribute/Type/OneToManyAttributeType.php#L78 "Oro\Bundle\EntityConfigBundle\Attribute\Type\OneToManyAttributeType::getSortableValue")</sup>
   - `ensureTraversable`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/EntityConfigBundle/Attribute/Type/OneToManyAttributeType.php#L87 "Oro\Bundle\EntityConfigBundle\Attribute\Type\OneToManyAttributeType::ensureTraversable")</sup>
* The `ConfigProvider::__construct(ConfigManager $configManager, $scope, PropertyConfigBag $configBag)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/EntityConfigBundle/Provider/ConfigProvider.php#L33 "Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider")</sup> method was changed to `ConfigProvider::__construct(ConfigManager $configManager, string $scope, PropertyConfigBag $configBag)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha/src/Oro/Bundle/EntityConfigBundle/Provider/ConfigProvider.php#L31 "Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider")</sup>
* The `OneToManyAttributeType::$entityNameResolver`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/EntityConfigBundle/Attribute/Type/OneToManyAttributeType.php#L18 "Oro\Bundle\EntityConfigBundle\Attribute\Type\OneToManyAttributeType::$entityNameResolver")</sup> property was removed.

EntityExtendBundle
------------------
* The `DynamicFieldsExtension::configureOptions`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/EntityExtendBundle/Form/Extension/DynamicFieldsExtension.php#L205 "Oro\Bundle\EntityExtendBundle\Form\Extension\DynamicFieldsExtension::configureOptions")</sup> method was removed.

LocaleBundle
------------
* The `LocalizedFallbackValueCollectionTransformer::__construct(ManagerRegistry $registry, $field)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/LocaleBundle/Form/DataTransformer/LocalizedFallbackValueCollectionTransformer.php#L54 "Oro\Bundle\LocaleBundle\Form\DataTransformer\LocalizedFallbackValueCollectionTransformer")</sup> method was changed to `LocalizedFallbackValueCollectionTransformer::__construct(ManagerRegistry $registry, $field, $valueClass)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha/src/Oro/Bundle/LocaleBundle/Form/DataTransformer/LocalizedFallbackValueCollectionTransformer.php#L61 "Oro\Bundle\LocaleBundle\Form\DataTransformer\LocalizedFallbackValueCollectionTransformer")</sup>
* The following methods in class `LocalizedFallbackValue`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/LocaleBundle/Entity/LocalizedFallbackValue.php#L102 "Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue")</sup> were removed:
   - `getFallbacks`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/LocaleBundle/Entity/LocalizedFallbackValue.php#L102 "Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue::getFallbacks")</sup>
   - `getId`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/LocaleBundle/Entity/LocalizedFallbackValue.php#L110 "Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue::getId")</sup>
   - `setFallback`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/LocaleBundle/Entity/LocalizedFallbackValue.php#L119 "Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue::setFallback")</sup>
   - `getFallback`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/LocaleBundle/Entity/LocalizedFallbackValue.php#L129 "Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue::getFallback")</sup>
   - `setString`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/LocaleBundle/Entity/LocalizedFallbackValue.php#L138 "Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue::setString")</sup>
   - `getString`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/LocaleBundle/Entity/LocalizedFallbackValue.php#L148 "Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue::getString")</sup>
   - `setText`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/LocaleBundle/Entity/LocalizedFallbackValue.php#L157 "Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue::setText")</sup>
   - `getText`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/LocaleBundle/Entity/LocalizedFallbackValue.php#L167 "Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue::getText")</sup>
   - `getLocalization`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/LocaleBundle/Entity/LocalizedFallbackValue.php#L175 "Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue::getLocalization")</sup>
   - `setLocalization`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/LocaleBundle/Entity/LocalizedFallbackValue.php#L184 "Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue::setLocalization")</sup>
   - `__toString`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/LocaleBundle/Entity/LocalizedFallbackValue.php#L194 "Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue::__toString")</sup>
   - `__clone`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/LocaleBundle/Entity/LocalizedFallbackValue.php#L208 "Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue::__clone")</sup>
* The following properties in class `LocalizedFallbackValue`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/LocaleBundle/Entity/LocalizedFallbackValue.php#L40 "Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue")</sup> were removed:
   - `$id`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/LocaleBundle/Entity/LocalizedFallbackValue.php#L40 "Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue::$id")</sup>
   - `$fallback`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/LocaleBundle/Entity/LocalizedFallbackValue.php#L54 "Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue::$fallback")</sup>
   - `$localization`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/LocaleBundle/Entity/LocalizedFallbackValue.php#L97 "Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue::$localization")</sup>

SecurityBundle
--------------
* The `AclProvider::__construct(AuthorizationCheckerInterface $authorizationChecker, TokenAccessorInterface $tokenAccessor, ManagerRegistry $doctrine)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/SecurityBundle/Layout/DataProvider/AclProvider.php#L27 "Oro\Bundle\SecurityBundle\Layout\DataProvider\AclProvider")</sup> method was changed to `AclProvider::__construct(AuthorizationCheckerInterface $authorizationChecker, ManagerRegistry $doctrine)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha/src/Oro/Bundle/SecurityBundle/Layout/DataProvider/AclProvider.php#L25 "Oro\Bundle\SecurityBundle\Layout\DataProvider\AclProvider")</sup>
* The `AclProvider::$tokenAccessor`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Bundle/SecurityBundle/Layout/DataProvider/AclProvider.php#L17 "Oro\Bundle\SecurityBundle\Layout\DataProvider\AclProvider::$tokenAccessor")</sup> property was removed.

Testing
-------
* The `EntityType::__construct(array $choices, $name, array $options = null)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.1.1/src/Oro/Component/Testing/Unit/Form/Type/Stub/EntityType.php#L32 "Oro\Component\Testing\Unit\Form\Type\Stub\EntityType")</sup> method was changed to `EntityType::__construct(array $choices = [], $name, array $options = null)`<sup>[[?]](https://github.com/oroinc/platform/tree/4.2.0-alpha/src/Oro/Component/Testing/Unit/Form/Type/Stub/EntityType.php#L32 "Oro\Component\Testing\Unit\Form\Type\Stub\EntityType")</sup>

