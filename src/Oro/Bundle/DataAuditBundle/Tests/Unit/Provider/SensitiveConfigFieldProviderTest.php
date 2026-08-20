<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\DataAuditBundle\Provider\SensitiveConfigFieldProvider;
use Oro\Bundle\FormBundle\Form\Type\OroEncodedPlaceholderPasswordType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormRegistryInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;

class SensitiveConfigFieldProviderTest extends TestCase
{
    private ConfigBag&MockObject $configBag;
    private FormRegistryInterface&MockObject $formRegistry;
    private SensitiveConfigFieldProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->configBag = $this->createMock(ConfigBag::class);
        $this->formRegistry = $this->createMock(FormRegistryInterface::class);

        $this->provider = new SensitiveConfigFieldProvider($this->configBag, $this->formRegistry);
    }

    public function testPasswordFieldIsSensitive(): void
    {
        $this->givenField('oro_email.smtp_settings_password', PasswordType::class);
        $this->givenFormType(PasswordType::class, new PasswordType());

        self::assertTrue($this->provider->isSensitive('oro_email.smtp_settings_password'));
    }

    public function testFieldOfATypeBuiltOnPasswordTypeIsSensitive(): void
    {
        // OroEncodedPlaceholderPasswordType is not a PasswordType, it only has it as a parent.
        $this->givenField('oro_form.recaptcha_private_key', OroEncodedPlaceholderPasswordType::class);
        $this->givenFormType(
            OroEncodedPlaceholderPasswordType::class,
            $this->createMock(FormTypeInterface::class),
            $this->resolvedType(new PasswordType())
        );

        self::assertTrue($this->provider->isSensitive('oro_form.recaptcha_private_key'));
    }

    public function testOrdinaryFieldIsNotSensitive(): void
    {
        $this->givenField('oro_form.recaptcha_public_key', TextType::class);
        $this->givenFormType(TextType::class, new TextType());

        self::assertFalse($this->provider->isSensitive('oro_form.recaptcha_public_key'));
    }

    public function testFieldWithoutDefinitionOrFormTypeIsNotSensitive(): void
    {
        $this->configBag->expects(self::any())
            ->method('getFieldsRoot')
            ->willReturnMap([
                ['oro_test.unknown', false],
                ['oro_test.no_type', ['data_type' => 'string']],
            ]);
        $this->formRegistry->expects(self::never())
            ->method('getType');

        self::assertFalse($this->provider->isSensitive('oro_test.unknown'));
        self::assertFalse($this->provider->isSensitive('oro_test.no_type'));
    }

    public function testFieldOfAnUnavailableFormTypeIsNotSensitive(): void
    {
        $this->givenField('oro_test.gone', 'Acme\Form\Type\RemovedType');
        $this->formRegistry->expects(self::once())
            ->method('getType')
            ->willThrowException(new InvalidArgumentException('Could not load type.'));

        self::assertFalse($this->provider->isSensitive('oro_test.gone'));
    }

    public function testResultIsMemoizedPerSetting(): void
    {
        $this->configBag->expects(self::once())
            ->method('getFieldsRoot')
            ->willReturn(['type' => PasswordType::class]);
        $this->formRegistry->expects(self::once())
            ->method('getType')
            ->willReturn($this->resolvedType(new PasswordType()));

        self::assertTrue($this->provider->isSensitive('oro_email.smtp_settings_password'));
        self::assertTrue($this->provider->isSensitive('oro_email.smtp_settings_password'));
    }

    private function givenField(string $configKey, string $formType): void
    {
        $this->configBag->expects(self::any())
            ->method('getFieldsRoot')
            ->with($configKey)
            ->willReturn(['data_type' => 'string', 'type' => $formType]);
    }

    private function givenFormType(
        string $formType,
        FormTypeInterface $innerType,
        ?ResolvedFormTypeInterface $parent = null
    ): void {
        $this->formRegistry->expects(self::any())
            ->method('getType')
            ->with($formType)
            ->willReturn($this->resolvedType($innerType, $parent));
    }

    private function resolvedType(
        FormTypeInterface $innerType,
        ?ResolvedFormTypeInterface $parent = null
    ): ResolvedFormTypeInterface {
        $resolvedType = $this->createMock(ResolvedFormTypeInterface::class);
        $resolvedType->expects(self::any())
            ->method('getInnerType')
            ->willReturn($innerType);
        $resolvedType->expects(self::any())
            ->method('getParent')
            ->willReturn($parent);

        return $resolvedType;
    }
}
