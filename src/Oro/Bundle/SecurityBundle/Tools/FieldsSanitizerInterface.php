<?php

namespace Oro\Bundle\SecurityBundle\Tools;

/**
 * An interface class for entity fields sanitizers.
 */
interface FieldsSanitizerInterface
{
    /**
     * Scans for entities of class $entityClass, looks into their fields of type $fieldType and strips tags from them.
     *
     * @param string $entityClass Class of entities to sanitize.
     * @param string $fieldTypeToSanitize Type of field to look into.
     * @param int $mode Sanitization mode.
     * @param array $modeArguments Extra arguments specific for the sanitization mode.
     * @param bool $applyChanges If true, persist sanitized data. Otherwise method returns entities ids and
     *                           their fields that should be sanitized.
     * @param int $chunkSize Number of rows for sanitizing to fetch at a time.
     *
     * @return iterable<array>
     *  [
     *      // Id of the entity with unsafe content in fields.
     *      int $entityId => [ // Array of fields with unsafe content.
     *          'sampleField1',
     *          'sampleField2',
     *           // ...
     *      ],
     *  ]
     */
    public function sanitizeByFieldType(
        string $entityClass,
        string $fieldTypeToSanitize,
        int $mode,
        array $modeArguments,
        bool $applyChanges,
        int $chunkSize = 1000
    ): iterable;
}
