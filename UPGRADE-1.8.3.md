UPGRADE FROM 1.8.2 to 1.8.3
===========================

####EmailBundle
- Command `oro:email:body-sync` was marked as deprecated
- Command `oro:cron:email-body-sync` was added

####ImportExportBundle
- The signature of `Oro\Bundle\ImportExportBundle\Converter\ConfigurableTableDataConverter::getRelatedEntityRulesAndBackendHeaders` method changed. Before: `getRelatedEntityRulesAndBackendHeaders($entityName, $fullData, $singleRelationDeepLevel, $multipleRelationDeepLevel, $field, $fieldHeader, $fieldOrder, $isIdentifier = false)`. After: `getRelatedEntityRulesAndBackendHeaders($entityName, $singleRelationDeepLevel, $multipleRelationDeepLevel, $field, $fieldHeader, $fieldOrder)`. This can bring a `backward compatibility break` if you have classes inherited from `Oro\Bundle\ImportExportBundle\Converter\ConfigurableTableDataConverter`.
