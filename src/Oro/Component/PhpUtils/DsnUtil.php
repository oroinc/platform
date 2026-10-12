<?php

declare(strict_types=1);

namespace Oro\Component\PhpUtils;

/**
 * Provides the static methods that read the TLS options of a service DSN and hide its secrets.
 *
 * Redis, RabbitMQ and Elasticsearch DSNs use them, so the three services validate the ssl[...] options in the same way.
 */
final class DsnUtil
{
    /** The PHP SSL context options that take a boolean. */
    private const array BOOLEAN_SSL_CONTEXT_OPTIONS = [
        'allow_self_signed',
        'capture_peer_cert',
        'capture_peer_cert_chain',
        'disable_compression',
        'SNI_enabled',
        'verify_peer',
        'verify_peer_name',
    ];

    /**
     * The PHP SSL context options that take an integer, with the largest value. PHP reads a text that is not a number
     * as 0, and cuts a number that is too large, for example security level 4294967296 becomes 0.
     */
    private const array INTEGER_SSL_CONTEXT_OPTIONS = [
        'crypto_method' => 2147483647,
        'security_level' => 5,
        'verify_depth' => 2147483647,
    ];

    /**
     * The PHP SSL context options that take a TLS protocol version: one of the STREAM_CRYPTO_PROTO_* values.
     * PHP reads another value, for example "1.3", as a version that is not correct or as no limit.
     */
    private const array PROTOCOL_VERSION_SSL_CONTEXT_OPTIONS = ['max_proto_version', 'min_proto_version'];

    private const array PROTOCOL_VERSIONS = [
        'TLS 1.0' => STREAM_CRYPTO_PROTO_TLSv1_0,
        'TLS 1.1' => STREAM_CRYPTO_PROTO_TLSv1_1,
        'TLS 1.2' => STREAM_CRYPTO_PROTO_TLSv1_2,
        'TLS 1.3' => STREAM_CRYPTO_PROTO_TLSv1_3,
    ];

    /** The PHP SSL context options that take a string, for example a path. */
    private const array STRING_SSL_CONTEXT_OPTIONS = [
        'cafile',
        'capath',
        'ciphers',
        'local_cert',
        'local_pk',
        'passphrase',
        'peer_name',
    ];

    /**
     * Gives the PHP SSL context options from the value of the "ssl" query parameter of a DSN. For example,
     * ssl[cafile]=/etc/ssl/ca.pem&ssl[verify_peer]=1 gives ['cafile' => '/etc/ssl/ca.pem', 'verify_peer' => true].
     *
     * A boolean option accepts 1, 0, true, false, yes, no, on and off. Another value causes an error, because PHP reads
     * a string such as "false" as true, and a typo must not turn off the verification. For the same reason, an integer
     * option accepts only a number from 0 to the largest value of the option, for example ssl[security_level]=2, and a
     * protocol version option accepts only a STREAM_CRYPTO_PROTO_* value, for example ssl[min_proto_version]=32.
     *
     * @param mixed  $ssl     The value of the "ssl" query parameter, null when the DSN does not have it.
     * @param string $dsnName The name of the DSN in the error messages, for example "Redis DSN".
     *
     * @throws \InvalidArgumentException if an option has a value of the wrong type or out of range
     */
    public static function getSslContextOptions(#[\SensitiveParameter] mixed $ssl, string $dsnName): array
    {
        if (null === $ssl) {
            return [];
        }
        if (!\is_array($ssl)) {
            throw new \InvalidArgumentException(sprintf(
                'The "ssl" parameter of the %s must contain named options, for example ssl[cafile].',
                $dsnName
            ));
        }

        foreach ($ssl as $name => $value) {
            $ssl[$name] = self::convertSslContextOption((string) $name, $value, $dsnName);
        }

        return $ssl;
    }

    /**
     * Gives the value of one option: a boolean, integer or protocol version option as a number or a boolean, another
     * option as it is.
     */
    private static function convertSslContextOption(
        string $name,
        #[\SensitiveParameter] mixed $value,
        string $dsnName
    ): mixed {
        if (\in_array($name, self::BOOLEAN_SSL_CONTEXT_OPTIONS, true)) {
            return self::getBooleanOption($name, $value, $dsnName);
        }
        if (isset(self::INTEGER_SSL_CONTEXT_OPTIONS[$name])) {
            return self::getIntegerOption($name, $value, $dsnName);
        }
        if (\in_array($name, self::PROTOCOL_VERSION_SSL_CONTEXT_OPTIONS, true)) {
            return self::getProtocolVersionOption($name, $value, $dsnName);
        }
        if (\in_array($name, self::STRING_SSL_CONTEXT_OPTIONS, true) && !\is_string($value)) {
            throw new \InvalidArgumentException(sprintf(
                'The ssl[%s] option of the %s must be a string.',
                $name,
                $dsnName
            ));
        }

        return $value;
    }

    /**
     * Gives the DSN without the password, the other credentials and the query, for use in an error message.
     * The query can contain secrets too, for example ssl[passphrase].
     */
    public static function hideSecrets(#[\SensitiveParameter] string $dsn): string
    {
        // The credentials start after the scheme, for example "amqps://" or "elastic-search:".
        $userInfoStart = preg_match('#^[a-z][a-z0-9+.\-]*:(//)?#i', $dsn, $matches) ? \strlen($matches[0]) : 0;
        // A password can contain "/", "?", "#" or "@" that are not encoded. Thus the credentials end at the last "@".
        $userInfoEnd = strrpos($dsn, '@', $userInfoStart);
        if (false !== $userInfoEnd) {
            $queryStart = strpos($dsn, '?', $userInfoStart);
            if (false !== $queryStart && $queryStart < $userInfoEnd) {
                // The "@" can be in the password or in the query. Thus all the text after the scheme is hidden.
                return substr($dsn, 0, $userInfoStart) . '***';
            }

            $dsn = substr($dsn, 0, $userInfoStart) . '***' . substr($dsn, $userInfoEnd);
        }

        return preg_replace('#\?.*$#s', '?***', $dsn);
    }

    private static function getIntegerOption(string $name, mixed $value, string $dsnName): int
    {
        $maxValue = self::INTEGER_SSL_CONTEXT_OPTIONS[$name];
        $integer = \is_string($value)
            ? filter_var(
                $value,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 0, 'max_range' => $maxValue], 'flags' => FILTER_NULL_ON_FAILURE]
            )
            : null;
        if (null === $integer) {
            throw new \InvalidArgumentException(sprintf(
                'The ssl[%s] option of the %s must be a number from 0 to %d.',
                $name,
                $dsnName,
                $maxValue
            ));
        }

        return $integer;
    }

    private static function getProtocolVersionOption(string $name, mixed $value, string $dsnName): int
    {
        $version = \is_string($value)
            ? filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE)
            : null;
        if (null === $version || !\in_array($version, self::PROTOCOL_VERSIONS, true)) {
            $versions = [];
            foreach (self::PROTOCOL_VERSIONS as $versionName => $versionValue) {
                $versions[] = sprintf('%d (%s)', $versionValue, $versionName);
            }

            throw new \InvalidArgumentException(sprintf(
                'The ssl[%s] option of the %s must be one of these values: %s.',
                $name,
                $dsnName,
                implode(', ', $versions)
            ));
        }

        return $version;
    }

    private static function getBooleanOption(string $name, mixed $value, string $dsnName): bool
    {
        $boolean = \is_string($value) && '' !== $value
            ? filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE)
            : null;
        if (null === $boolean) {
            throw new \InvalidArgumentException(sprintf(
                'The ssl[%s] option of the %s must be 1, 0, true, false, yes, no, on or off.',
                $name,
                $dsnName
            ));
        }

        return $boolean;
    }
}
