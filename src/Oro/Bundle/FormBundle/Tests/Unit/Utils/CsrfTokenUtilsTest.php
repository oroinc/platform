<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Utils;

use Oro\Bundle\FormBundle\Utils\CsrfTokenUtils;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CsrfTokenUtilsTest extends TestCase
{
    /**
     * @dataProvider isCsrfProtectionEnabledDataProvider
     */
    public function testIsCsrfProtectionEnabled(bool $csrfProtection): void
    {
        $form = $this->createMock(FormInterface::class);
        $formConfig = $this->createMock(FormConfigInterface::class);

        $form
            ->method('getConfig')
            ->willReturn($formConfig);

        $formConfig
            ->expects(self::once())
            ->method('getOption')
            ->with('csrf_protection')
            ->willReturn($csrfProtection);

        self::assertSame($csrfProtection, CsrfTokenUtils::isCsrfProtectionEnabled($form));
    }

    public function isCsrfProtectionEnabledDataProvider(): array
    {
        return [[true], [false]];
    }

    public function testGetCsrfTokenMustReturnNullWhenCsrfProtectionNotEnabled(): void
    {
        $form = $this->createMock(FormInterface::class);
        $formConfig = $this->createMock(FormConfigInterface::class);

        $form
            ->method('getConfig')
            ->willReturn($formConfig);

        $formConfig
            ->expects(self::once())
            ->method('getOption')
            ->with('csrf_protection')
            ->willReturn(false);

        self::assertNull(CsrfTokenUtils::getCsrfToken($form));
    }

    public function testGetCsrfTokenMustUseCsrfTokenId(): void
    {
        $form = $this->createMock(FormInterface::class);
        $formConfig = $this->createMock(FormConfigInterface::class);

        $form
            ->method('getConfig')
            ->willReturn($formConfig);

        $tokenId = 'sample_token_id';
        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);

        $formConfig
            ->method('getOption')
            ->willReturnMap([
                ['csrf_protection', null, true],
                ['csrf_token_id', null, $tokenId],
                ['csrf_token_manager', null, $csrfTokenManager],
            ]);

        $csrfToken = new CsrfToken($tokenId, 'sample_value');
        $csrfTokenManager
            ->expects(self::once())
            ->method('getToken')
            ->with($tokenId)
            ->willReturn($csrfToken);

        self::assertSame($csrfToken, CsrfTokenUtils::getCsrfToken($form));
    }

    public function testGetCsrfTokenMustUseFormNameWhenNoCsrfTokenId(): void
    {
        $form = $this->createMock(FormInterface::class);
        $formConfig = $this->createMock(FormConfigInterface::class);

        $form
            ->method('getConfig')
            ->willReturn($formConfig);

        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);

        $formConfig
            ->method('getOption')
            ->willReturnMap([
                ['csrf_protection', null, true],
                ['csrf_token_id', null, null],
                ['csrf_token_manager', null, $csrfTokenManager],
            ]);

        $formName = 'sample_form_name';
        $form
            ->expects(self::once())
            ->method('getName')
            ->willReturn($formName);

        $csrfToken = new CsrfToken($formName, 'sample_value');
        $csrfTokenManager
            ->expects(self::once())
            ->method('getToken')
            ->with($formName)
            ->willReturn($csrfToken);

        self::assertSame($csrfToken, CsrfTokenUtils::getCsrfToken($form));
    }

    public function testGetCsrfTokenMustUseFormInnerTypeWhenNoFormName(): void
    {
        $form = $this->createMock(FormInterface::class);
        $formConfig = $this->createMock(FormConfigInterface::class);

        $form
            ->method('getConfig')
            ->willReturn($formConfig);

        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);

        $formConfig
            ->method('getOption')
            ->willReturnMap([
                ['csrf_protection', null, true],
                ['csrf_token_id', null, null],
                ['csrf_token_manager', null, $csrfTokenManager],
            ]);

        $resolvedFormType = $this->createMock(ResolvedFormTypeInterface::class);
        $formConfig
            ->expects(self::once())
            ->method('getType')
            ->willReturn($resolvedFormType);

        $innerType = $this->createMock(FormTypeInterface::class);
        $resolvedFormType
            ->expects(self::once())
            ->method('getInnerType')
            ->willReturn($innerType);

        $innerTypeClass = \get_class($innerType);
        $form
            ->expects(self::once())
            ->method('getName')
            ->willReturn('');

        $csrfToken = new CsrfToken($innerTypeClass, 'sample_value');
        $csrfTokenManager
            ->expects(self::once())
            ->method('getToken')
            ->with($innerTypeClass)
            ->willReturn($csrfToken);

        self::assertSame($csrfToken, CsrfTokenUtils::getCsrfToken($form));
    }

    public function testGetCsrfFieldNameMustReturnNullWhenCsrfProtectionNotEnabled(): void
    {
        $form = $this->createMock(FormInterface::class);
        $formConfig = $this->createMock(FormConfigInterface::class);

        $form
            ->method('getConfig')
            ->willReturn($formConfig);

        $formConfig
            ->expects(self::once())
            ->method('getOption')
            ->with('csrf_protection')
            ->willReturn(false);

        self::assertNull(CsrfTokenUtils::getCsrfFieldName($form));
    }

    public function testGetCsrfFieldName(): void
    {
        $form = $this->createMock(FormInterface::class);
        $formConfig = $this->createMock(FormConfigInterface::class);

        $form
            ->method('getConfig')
            ->willReturn($formConfig);

        $csrfFieldName = 'sample_field_name';
        $formConfig
            ->method('getOption')
            ->willReturnMap([
                ['csrf_protection', null, true],
                ['csrf_field_name', null, $csrfFieldName],
            ]);

        self::assertSame($csrfFieldName, CsrfTokenUtils::getCsrfFieldName($form));
    }
}
