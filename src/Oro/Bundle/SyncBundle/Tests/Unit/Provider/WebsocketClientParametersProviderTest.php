<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Provider;

use Oro\Bundle\SyncBundle\Provider\WebsocketClientParametersProvider;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class WebsocketClientParametersProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider invalidTransportsProvider
     */
    public function testInvalidTransportGiven(string $transport): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(sprintf(
            'Transport "%s" is not available, please run stream_get_transports() to verify'
            . ' the list of registered transports.',
            $transport
        ));
        new WebsocketClientParametersProvider(sprintf('%s://*:8080', $transport));
    }

    public function invalidTransportsProvider(): array
    {
        return [['invalid-1'], ['2-invalid'], ['inv-3-lid']];
    }

    /**
     * @dataProvider nonExistentContextOptionProvider
     */
    public function testNonExistentContextOptionGiven(string $optionName): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(sprintf(
            'Unknown socket context option "%s". Only SSL context options '
            . '(http://php.net/manual/en/context.ssl.php) are allowed.',
            $optionName
        ));
        new WebsocketClientParametersProvider(sprintf('//*:8080?context_options[%s]=val', $optionName));
    }

    public function nonExistentContextOptionProvider(): array
    {
        return [['non_existent_option_1'], ['non_existent_option_2'], ['non_existent_option_3']];
    }

    public function testNotAnArrayContextOptionsGiven(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Socket context options must be an array');
        new WebsocketClientParametersProvider('//*:8080?context_options=val');
    }

    /**
     * @dataProvider invalidContextOptionTypesByValueProvider
     */
    public function testInvalidContextOptionTypesByValuesGiven(
        string $optionType,
        string $optionName,
        mixed $optionValue
    ): void {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(sprintf(
            'Invalid type of socket context option "%s", expected "%s" type.',
            $optionName,
            $optionType
        ));
        new WebsocketClientParametersProvider(sprintf(
            '//*:8080?context_options[%s]=%s',
            $optionName,
            $optionValue
        ));
    }

    public function invalidContextOptionTypesByValueProvider(): array
    {
        return [
            'wrong_boolean_1' => ['boolean', 'verify_peer', 'string_val'],
            'wrong_boolean_2' => ['boolean', 'verify_peer_name', '123'],
            'wrong_integer_1' => ['integer', 'verify_depth', 'string_val' ],
            'wrong_integer_2' => ['integer', 'verify_depth', '123string_part' ],
            'wrong_integer_3' => ['integer', 'verify_depth', 'string_part2133' ],
            'non_array_1' => ['array', 'peer_fingerprint', 'string_val'],
            'non_array_2' => ['array', 'peer_fingerprint', true],
            'non_array_3' => ['array', 'peer_fingerprint', 123]
        ];
    }

    /**
     * @dataProvider properContextOptionsProvider
     */
    public function testProperGivenContextOptionsNormalization(
        string $optionName,
        mixed $optionValue,
        array $expectedContextOptions
    ): void {
        $contextOptionsString = http_build_query(['context_options' => [$optionName => $optionValue]]);
        $wsClientParamsProvider =
            new WebsocketClientParametersProvider(sprintf('//*:8080?%s', $contextOptionsString));
        self::assertEquals($wsClientParamsProvider->getContextOptions(), $expectedContextOptions);
    }

    public function properContextOptionsProvider(): array
    {
        return [
            ['peer_name', 'string_val', ['peer_name'=> 'string_val']],
            ['verify_peer', 'true', ['verify_peer' => true]],
            ['verify_peer_name', 'on', ['verify_peer_name' => true]],
            ['allow_self_signed', 1, ['allow_self_signed' => true]],
            ['cafile', 12345, ['cafile'=> '12345']],
            ['capath', 'true', ['capath'=> 'true']],
            ['local_cert', 'on', ['local_cert'=> 'on']],
            ['local_pk', 'false', ['local_pk'=> 'false']],
            ['passphrase', 'off', ['passphrase'=> 'off']],
            ['CN_match', 'string_val', ['CN_match'=> 'string_val']],
            ['verify_depth', 123, ['verify_depth'=> 123]],
            ['ciphers', 'string_val', ['ciphers'=> 'string_val']],
            ['capture_peer_cert', 'false', ['capture_peer_cert' => false]],
            ['capture_peer_cert_chain', 'off', ['capture_peer_cert_chain' => false]],
            ['SNI_enabled', 0, ['SNI_enabled' => false]],
            ['SNI_server_name', 'string_val', ['SNI_server_name'=> 'string_val']],
            ['disable_compression', 'yes', ['disable_compression' => true]],
            ['disable_compression', 'no', ['disable_compression' => false]],
            ['peer_fingerprint', ['ab', 'cd'], ['peer_fingerprint' => ['ab', 'cd']]],
        ];
    }
}
