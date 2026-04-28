<?php

namespace Oro\Component\PhpUtils;

use Oro\Component\PhpUtils\Exception\UnsafeUnserializationException;
use Psr\Log\LoggerInterface;

/**
 * Provides a secure mechanism to unserialize serialized data in PHP while enforcing
 * restrictions on deserializable classes. This mechanism ensures that the unserialized
 * data conforms to a specific set of allowed or disallowed classes determined by
 * environment variables.
 */
class PhpUnserializer implements PhpUnserializerInterface
{
    protected const ALLOWED_CORE_CLASSES = [
        'datetime',
        'datetimeimmutable',
        'datetimezone',
        'dateinterval',
        'dateperiod',
        'doctrine\\common\\collections\\arraycollection',
        'doctrine\\orm\\persistentcollection'
    ];

    protected const DEFAULT_TRUSTED_ORGS = ['oro\\'];
    protected const DEFAULT_NOT_ALLOWED_CLASSES = [
        'oro\\bundle\\gaufrettebundle\\filemanager',
        'oro\\bundle\\attachmentbundle\\manager\\file\\temporaryfile',
        'oro\\bundle\\pdfgeneratorbundle\\pdffile\\pdffile',
        'oro\\bundle\\dotmailerbundle\\provider\\csvstringreader',
        'oro\\bundle\\batchbundle\\monolog\\handler\\batchloghandler',
        'oro\\bundle\\maintenancebundle\\drivers\\filedriver',
        'oro\\bundle\\elasticsearchbundle\\resultsetiterator\\searchresponseiterator',
        'oro\\bundle\\redisconfigbundle\\session\\storage\\handler\\redislockingsessionhandler',
        'oro\\bundle\\emailbundle\\mailer\\transport\\transport',
        'oro\\bundle\\loggerbundle\\monolog\\disablehandlerwrapper',
        'oro\\bundle\\searchbundle\\query\\indexerquery',
        'oro\\component\\layout\\dataproviderdecorator',
        'oro\\bundle\\importexportbundle\\file\\filemanager',
    ];

    private array $trustedOrgs;
    private array $notAllowedClasses;

    /**
     * @param LoggerInterface $logger
     * @param array|string[] $trustedOrgs - allowed namespace patterns that are allowed to be used in unserialization
     * @param array|string[] $notAllowedClasses - classes that are not allowed to be used in unserialization
     * @param bool $blockOnFailure - in case of detecting unsafe unserialization, throw exception and skip unserialize
     */
    public function __construct(
        private LoggerInterface $logger,
        array $trustedOrgs,
        array $notAllowedClasses,
        private bool $blockOnFailure = true
    ) {
        $this->trustedOrgs = $this->normalizeArray($trustedOrgs);
        $this->notAllowedClasses = $this->normalizeArray($notAllowedClasses);
    }

    #[\Override]
    public function unserialize(string $value, array $options = []): mixed
    {
        if (!isset($options['allowed_classes']) || true === $options['allowed_classes']) {
            $whitelistedClasses = $options[static::WHITELIST_CLASSES_KEY] ?? [];
            unset($options[static::WHITELIST_CLASSES_KEY]);

            try {
                $this->checkSerializedString($value, $whitelistedClasses);
            } catch (UnsafeUnserializationException $e) {
                $this->logger->critical(
                    'Unserialization failed',
                    [
                        'exception' => $e
                    ]
                );

                if ($this->blockOnFailure) {
                    throw $e;
                }
            }
        }

        return \unserialize($value, $options);
    }

    #[\Override]
    public function checkSerializedString(string $value, array $whitelistedClasses = []): void
    {
        $whitelistedClasses = $this->normalizeArray($whitelistedClasses);
        // Remove null bytes from the string to handle private/protected property name encoding
        $checkValue = str_replace("\0", '', $value);

        // RegExp to find classes used in serialized data
        preg_match_all('/[CEO]:\+*\d+:"([a-zA-Z_\\\\][a-zA-Z0-9_\\\\]*)/', $checkValue, $matches);
        $usedClasses = $matches[1] ?? [];

        $usedClasses = array_unique($usedClasses);
        // Make all values lowercase to avoid case-sensitive comparisons
        $usedClasses = $this->normalizeArray($usedClasses);
        // Remove leading backslashes and doctrine proxy class prefix to normalize class name for further checks
        $usedClasses = array_map(
            static function (string $className) {
                $className = ltrim($className, '\\');
                $className = str_replace('proxies\\__cg__\\', '', $className);

                return $className;
            },
            $usedClasses
        );

        // Get allowed and disallowed classes from env
        $allowedOrgs = array_merge(static::DEFAULT_TRUSTED_ORGS, $this->trustedOrgs);
        $notAllowedClasses = array_merge(
            static::DEFAULT_NOT_ALLOWED_CLASSES,
            $this->notAllowedClasses
        );

        // Check if there are classes that are not allowed
        $foundNotAllowedClasses = array_intersect($usedClasses, $notAllowedClasses);
        if ($foundNotAllowedClasses) {
            throw UnsafeUnserializationException::create($foundNotAllowedClasses);
        }

        // Remove classes that are allowed
        $whitelistedClasses = array_merge($whitelistedClasses, static::ALLOWED_CORE_CLASSES);
        $usedClasses = array_diff($usedClasses, $whitelistedClasses);
        // Check for classes outside allowed orgs, do not allow test classes to be referenced
        foreach ($allowedOrgs as $org) {
            $usedClasses = array_filter(
                $usedClasses,
                fn ($className) => str_contains($className, '\\tests\\') || !str_starts_with($className, $org)
            );
        }
        // There are still unfiltered classes
        if ($usedClasses) {
            throw UnsafeUnserializationException::create($usedClasses);
        }
    }

    private function normalizeArray(array $data): array
    {
        // Make all values lowercase to avoid case-sensitive comparisons
        $data = array_map('strtolower', $data);
        // Remove empty values
        $data = array_filter($data);

        return $data;
    }
}
