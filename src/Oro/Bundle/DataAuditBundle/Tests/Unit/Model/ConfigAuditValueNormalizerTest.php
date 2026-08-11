<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Model;

use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\DataAuditBundle\Model\ConfigAuditValueNormalizer;
use Oro\Bundle\DataAuditBundle\Provider\SensitiveConfigFieldProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigAuditValueNormalizerTest extends TestCase
{
    private ConfigBag&MockObject $configBag;
    private SensitiveConfigFieldProvider&MockObject $sensitiveFieldProvider;
    private ConfigAuditValueNormalizer $normalizer;

    #[\Override]
    protected function setUp(): void
    {
        $this->configBag = $this->createMock(ConfigBag::class);
        $this->sensitiveFieldProvider = $this->createMock(SensitiveConfigFieldProvider::class);

        $this->normalizer = new ConfigAuditValueNormalizer($this->configBag, $this->sensitiveFieldProvider);
    }

    public function testMasksTheValuesOfASensitiveSetting(): void
    {
        $this->sensitiveFieldProvider->expects(self::any())
            ->method('isSensitive')
            ->with('oro_email.smtp_settings_password')
            ->willReturn(true);
        $this->configBag->expects(self::never())
            ->method('getFieldsRoot');

        // The audit records that the secret changed, never what it changed to.
        self::assertSame(
            ['type' => 'text', 'old' => '***', 'new' => '***'],
            $this->normalizer->normalize('oro_email.smtp_settings_password', 'old secret', 'new secret')
        );
    }

    public function testKeepsTheInformativeSideOnlyForASensitiveSetting(): void
    {
        $this->sensitiveFieldProvider->expects(self::any())
            ->method('isSensitive')
            ->willReturn(true);

        // A secret set for the first time has no previous value to hide.
        self::assertSame(
            ['type' => 'text', 'old' => null, 'new' => '***'],
            $this->normalizer->normalize('oro_email.smtp_settings_password', null, 'secret')
        );
    }

    /**
     * @dataProvider normalizeDataProvider
     */
    public function testNormalize(?string $dataType, mixed $old, mixed $new, array $expected): void
    {
        $this->configBag->expects(self::any())
            ->method('getFieldsRoot')
            ->with('oro_test.setting')
            ->willReturn(null === $dataType ? false : ['data_type' => $dataType]);

        self::assertSame($expected, $this->normalizer->normalize('oro_test.setting', $old, $new));
    }

    public function normalizeDataProvider(): array
    {
        return [
            'boolean stays a real boolean, not "1"/"0"' => [
                'boolean',
                '1',
                '0',
                ['type' => 'boolean', 'old' => true, 'new' => false],
            ],
            'integer' => ['integer', '25', 42, ['type' => 'integer', 'old' => 25, 'new' => 42]],
            'decimal becomes a float' => ['decimal', '0.5', '1', ['type' => 'float', 'old' => 0.5, 'new' => 1.0]],
            'array keeps its list' => [
                'array',
                ['paypal'],
                ['paypal', 'stripe'],
                ['type' => 'array', 'old' => ['paypal'], 'new' => ['paypal', 'stripe']],
            ],
            'an empty array means "no value"' => [
                'array',
                ['paypal'],
                [],
                ['type' => 'array', 'old' => ['paypal'], 'new' => null],
            ],
            'a scalar of an array setting is wrapped' => [
                'array',
                'paypal',
                ['paypal'],
                ['type' => 'array', 'old' => ['paypal'], 'new' => ['paypal']],
            ],
            'string is stored as text' => [
                'string',
                'old value',
                'new value',
                ['type' => 'text', 'old' => 'old value', 'new' => 'new value'],
            ],
            'a data type the audit does not know becomes text' => [
                'number',
                '1',
                '2',
                ['type' => 'text', 'old' => '1', 'new' => '2'],
            ],
            'a setting without a definition becomes text' => [
                null,
                'a',
                'b',
                ['type' => 'text', 'old' => 'a', 'new' => 'b'],
            ],
            'a list of scalars renders comma separated' => [
                'string',
                ['x', 'y'],
                ['z'],
                ['type' => 'text', 'old' => 'x, y', 'new' => 'z'],
            ],
            'anything else renders as JSON' => [
                'string',
                ['month' => 1, 'day' => 1],
                null,
                ['type' => 'text', 'old' => '{"month":1,"day":1}', 'new' => null],
            ],
            'booleans of a text setting render as "1"/"0"' => [
                'string',
                true,
                false,
                ['type' => 'text', 'old' => '1', 'new' => '0'],
            ],
            'nothing on either side' => [null, null, null, ['type' => 'text', 'old' => null, 'new' => null]],
        ];
    }

    public function testNormalizeFormatsDateTime(): void
    {
        $this->configBag->expects(self::any())
            ->method('getFieldsRoot')
            ->willReturn(['data_type' => 'string']);

        self::assertSame(
            ['type' => 'text', 'old' => null, 'new' => '2026-07-29 10:30:00'],
            $this->normalizer->normalize(
                'oro_test.setting',
                null,
                new \DateTime('2026-07-29 10:30:00', new \DateTimeZone('UTC'))
            )
        );
    }
}
