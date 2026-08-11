<?php

namespace Oro\Bundle\DataAuditBundle\Provider;

use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Base for providers that answer a single question: which human-readable name does the audit grid show
 * for a changed field, and which fields match a search term.
 *
 * A subclass only builds the dictionary [field name => displayed name] of one object class. Translating
 * it once per locale, looking a name up and matching a term against it all live here — so the name the
 * grid renders and the name the "Data" filter searches can never drift apart.
 */
abstract class AbstractAuditFieldNameProvider
{
    /** ["<object class>|<locale>" => [field name => displayed name]] */
    private array $names = [];

    public function __construct(protected readonly TranslatorInterface $translator)
    {
    }

    /**
     * @return string[] object classes this provider knows the field names of
     */
    abstract protected function getObjectClasses(): array;

    /**
     * @return array<string, string> [field name => displayed name] of a single object class
     */
    abstract protected function buildNamesFor(string $objectClass): array;

    protected function getName(string $objectClass, string $fieldName): ?string
    {
        return $this->getNames($objectClass)[$fieldName] ?? null;
    }

    /**
     * Fields whose displayed name contains the term, as two sets ready to be used as a pair of SQL IN
     * lists (field names are not unique across object classes).
     *
     * @return array{classes: string[], fields: string[]}
     */
    protected function matchFields(string $term): array
    {
        $term = mb_strtolower(trim($term));
        if ('' === $term) {
            return ['classes' => [], 'fields' => []];
        }

        $classes = [];
        $fields = [];
        foreach ($this->getObjectClasses() as $objectClass) {
            foreach ($this->getNames($objectClass) as $fieldName => $name) {
                if (str_contains(mb_strtolower($name), $term)) {
                    $classes[$objectClass] = true;
                    $fields[$fieldName] = true;
                }
            }
        }

        return ['classes' => array_keys($classes), 'fields' => array_keys($fields)];
    }

    /**
     * Memoized per object class AND locale: the names are translated, so the dictionary must always
     * match what the current viewer sees and be rebuilt when the locale or a translation changes.
     *
     * @return array<string, string>
     */
    private function getNames(string $objectClass): array
    {
        $locale = $this->translator instanceof LocaleAwareInterface ? $this->translator->getLocale() : '';
        $cacheKey = $objectClass . '|' . $locale;
        if (!isset($this->names[$cacheKey])) {
            $this->names[$cacheKey] = $this->buildNamesFor($objectClass);
        }

        return $this->names[$cacheKey];
    }
}
