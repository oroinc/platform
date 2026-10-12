<?php

namespace Oro\Component\PhpUtils\Tests\Unit;

use Oro\Component\PhpUtils\DsnUtil;
use PHPUnit\Framework\TestCase;

class DsnUtilTest extends TestCase
{
    public function testGetSslContextOptionsWithoutSslParameter(): void
    {
        self::assertSame([], DsnUtil::getSslContextOptions(null, 'test DSN'));
    }

    public function testGetSslContextOptionsConvertsBooleanOptions(): void
    {
        $options = DsnUtil::getSslContextOptions(
            [
                'allow_self_signed' => 'false',
                'capture_peer_cert' => 'yes',
                'capture_peer_cert_chain' => 'off',
                'disable_compression' => 'on',
                'SNI_enabled' => 'no',
                'verify_peer' => '1',
                'verify_peer_name' => 'TRUE',
            ],
            'test DSN'
        );

        self::assertSame(
            [
                'allow_self_signed' => false,
                'capture_peer_cert' => true,
                'capture_peer_cert_chain' => false,
                'disable_compression' => true,
                'SNI_enabled' => false,
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
            $options
        );
    }

    public function testGetSslContextOptionsKeepsOtherOptions(): void
    {
        $ssl = [
            'cafile' => '/etc/ssl/ca.pem',
            'peer_name' => 'redis.example.com',
            'peer_fingerprint' => ['sha256' => 'abc'],
        ];

        self::assertSame($ssl, DsnUtil::getSslContextOptions($ssl, 'test DSN'));
    }

    public function testGetSslContextOptionsConvertsIntegerOptions(): void
    {
        self::assertSame(
            ['security_level' => 2, 'verify_depth' => 0, 'crypto_method' => 121],
            DsnUtil::getSslContextOptions(
                ['security_level' => '2', 'verify_depth' => '0', 'crypto_method' => '121'],
                'test DSN'
            )
        );
    }

    public function testGetSslContextOptionsAcceptsTheLargestIntegerValues(): void
    {
        self::assertSame(
            ['security_level' => 5, 'verify_depth' => 2147483647, 'crypto_method' => 2147483647],
            DsnUtil::getSslContextOptions(
                ['security_level' => '5', 'verify_depth' => '2147483647', 'crypto_method' => '2147483647'],
                'test DSN'
            )
        );
    }

    public function testGetSslContextOptionsConvertsProtocolVersionOptions(): void
    {
        self::assertSame(
            ['min_proto_version' => STREAM_CRYPTO_PROTO_TLSv1_2, 'max_proto_version' => STREAM_CRYPTO_PROTO_TLSv1_3],
            DsnUtil::getSslContextOptions(
                [
                    'min_proto_version' => (string) STREAM_CRYPTO_PROTO_TLSv1_2,
                    'max_proto_version' => (string) STREAM_CRYPTO_PROTO_TLSv1_3,
                ],
                'test DSN'
            )
        );
    }

    /**
     * @dataProvider invalidProtocolVersionDataProvider
     */
    public function testGetSslContextOptionsRejectsInvalidProtocolVersion(string $name, mixed $value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'The ssl[%s] option of the test DSN must be one of these values: %d (TLS 1.0), %d (TLS 1.1), %d (TLS 1.2), '
            . '%d (TLS 1.3).',
            $name,
            STREAM_CRYPTO_PROTO_TLSv1_0,
            STREAM_CRYPTO_PROTO_TLSv1_1,
            STREAM_CRYPTO_PROTO_TLSv1_2,
            STREAM_CRYPTO_PROTO_TLSv1_3
        ));

        DsnUtil::getSslContextOptions([$name => $value], 'test DSN');
    }

    public function invalidProtocolVersionDataProvider(): array
    {
        return [
            'version number' => ['min_proto_version', '1.3'],
            'version name' => ['min_proto_version', 'TLSv1.3'],
            'number that PHP cuts to 0' => ['min_proto_version', '4294967296'],
            'SSL 3' => ['min_proto_version', (string) STREAM_CRYPTO_PROTO_SSLv3],
            'empty' => ['max_proto_version', ''],
            'array' => ['max_proto_version', ['64']],
        ];
    }

    /**
     * @dataProvider invalidIntegerDataProvider
     */
    public function testGetSslContextOptionsRejectsInvalidInteger(string $name, mixed $value, int $maxValue): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf('The ssl[%s] option of the test DSN must be a number from 0 to %d.', $name, $maxValue)
        );

        DsnUtil::getSslContextOptions([$name => $value], 'test DSN');
    }

    public function invalidIntegerDataProvider(): array
    {
        return [
            'word' => ['security_level', 'two', 5],
            'empty' => ['security_level', '', 5],
            'negative' => ['verify_depth', '-1', 2147483647],
            'array' => ['crypto_method', ['1'], 2147483647],
            'security level above the largest' => ['security_level', '6', 5],
            'number that PHP cuts to 0' => ['security_level', '4294967296', 5],
            'depth above the largest' => ['verify_depth', '2147483648', 2147483647],
        ];
    }

    public function testGetSslContextOptionsRejectsSslWithoutNamedOptions(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The "ssl" parameter of the test DSN must contain named options, for example ssl[cafile].'
        );

        DsnUtil::getSslContextOptions('1', 'test DSN');
    }

    /**
     * @dataProvider invalidBooleanDataProvider
     */
    public function testGetSslContextOptionsRejectsInvalidBoolean(string $name, mixed $value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'The ssl[%s] option of the test DSN must be 1, 0, true, false, yes, no, on or off.',
            $name
        ));

        DsnUtil::getSslContextOptions([$name => $value], 'test DSN');
    }

    public function invalidBooleanDataProvider(): array
    {
        return [
            'typo' => ['verify_peer', 'ture'],
            'empty' => ['verify_peer_name', ''],
            'array' => ['allow_self_signed', ['false']],
            'unknown word' => ['SNI_enabled', 'maybe'],
        ];
    }

    public function testGetSslContextOptionsRejectsStringOptionOfAnotherType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The ssl[cafile] option of the test DSN must be a string.');

        DsnUtil::getSslContextOptions(['cafile' => ['/etc/ssl/ca.pem']], 'test DSN');
    }

    /**
     * @dataProvider hideSecretsDataProvider
     */
    public function testHideSecrets(string $dsn, string $expected): void
    {
        self::assertSame($expected, DsnUtil::hideSecrets($dsn));
    }

    public function hideSecretsDataProvider(): array
    {
        return [
            'no secrets' => ['dbal:', 'dbal:'],
            'host only' => ['amqp://localhost:5672/%2f', 'amqp://localhost:5672/%2f'],
            'credentials' => ['amqps://oro:secret@mq:5671/%2f', 'amqps://***@mq:5671/%2f'],
            'credentials and query' => [
                'https://elastic:secret@es:9200?prefix=oro&ssl[passphrase]=pass',
                'https://***@es:9200?***',
            ],
            'query only' => ['https://es:9200?ssl[passphrase]=pass', 'https://es:9200?***'],
            'password with reserved characters' => [
                'amqps://oro:p/a:s#s@mq:5671/%2f',
                'amqps://***@mq:5671/%2f',
            ],
            '"@" in the query' => [
                'amqps://oro:secret@mq:5671/%2f?ssl[passphrase]=p@ssw0rd',
                'amqps://***',
            ],
            '"@" in the query, no credentials' => ['https://es:9200/path?ssl[passphrase]=top@secret', 'https://***'],
            '"?" in the password' => ['amqps://oro:p?ss@mq:5671/%2f', 'amqps://***'],
            'scheme without slashes' => [
                'elastic-search:elastic:secret@es:9200/path?callback=http://x',
                'elastic-search:***@es:9200/path?***',
            ],
            'multiline value' => ["amqp://mq?ssl[passphrase]=a\nb", 'amqp://mq?***'],
        ];
    }
}
