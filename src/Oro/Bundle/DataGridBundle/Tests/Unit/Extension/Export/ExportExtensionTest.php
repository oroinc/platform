<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Export;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\Export\ExportExtension;
use Oro\Bundle\ImportExportBundle\Entity\ImportExportResult;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ExportExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var ExportExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function (string $id): string {
                return $id . ' (translated)';
            });

        $this->extension = new ExportExtension(
            $translator,
            $this->authorizationChecker,
            $this->tokenStorage
        );
    }

    private function expectsGranted(): void
    {
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));
        $this->authorizationChecker->expects(self::exactly(2))
            ->method('isGranted')
            ->withConsecutive(
                ['oro_datagrid_gridview_export'],
                ['VIEW', 'entity:' . ImportExportResult::class]
            )
            ->willReturn(true);
    }

    public function testIsApplicableWithoutSecurityContext(): void
    {
        $config = DatagridConfiguration::create([]);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);

        $this->extension->setParameters(new ParameterBag());
        self::assertFalse($this->extension->isApplicable($config));
    }

    public function testIsApplicableWhenGridExportCapabilityIsDisabled(): void
    {
        $config = DatagridConfiguration::create([]);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('oro_datagrid_gridview_export')
            ->willReturn(false);

        $this->extension->setParameters(new ParameterBag());
        self::assertFalse($this->extension->isApplicable($config));
    }

    public function testIsApplicableWhenViewPermissionToImportExportResultEntityIsDisabled(): void
    {
        $config = DatagridConfiguration::create([]);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));
        $this->authorizationChecker->expects(self::exactly(2))
            ->method('isGranted')
            ->withConsecutive(
                ['oro_datagrid_gridview_export'],
                ['VIEW', 'entity:' . ImportExportResult::class]
            )
            ->willReturnOnConsecutiveCalls(true, false);

        $this->extension->setParameters(new ParameterBag());
        self::assertFalse($this->extension->isApplicable($config));
    }

    public function testIsApplicableWhenGridExportOptionIsNotSpecified(): void
    {
        $config = DatagridConfiguration::create([]);

        $this->expectsGranted();

        $this->extension->setParameters(new ParameterBag());
        self::assertFalse($this->extension->isApplicable($config));
    }

    public function testIsApplicableWhenGridExportIsDisabled(): void
    {
        $config = DatagridConfiguration::create(['options' => ['export' => false]]);

        $this->expectsGranted();

        $this->extension->setParameters(new ParameterBag());
        self::assertFalse($this->extension->isApplicable($config));
    }

    public function testIsApplicableWhenGridExportIsEnabled(): void
    {
        $config = DatagridConfiguration::create(['options' => ['export' => true]]);

        $this->expectsGranted();

        $this->extension->setParameters(new ParameterBag());
        self::assertTrue($this->extension->isApplicable($config));
    }

    public function testIsApplicableWhenGridExportIsEnabledForSomeFormats(): void
    {
        $config = DatagridConfiguration::create(['options' => ['export' => ['csv' => ['label' => 'some_label']]]]);

        $this->expectsGranted();

        $this->extension->setParameters(new ParameterBag());
        self::assertTrue($this->extension->isApplicable($config));
    }


    public function testProcessConfigsWhenGridExportOptionIsNotSpecified(): void
    {
        $config = DatagridConfiguration::create([]);

        $this->extension->setParameters(new ParameterBag());
        $this->extension->processConfigs($config);

        self::assertSame([], $config->offsetGetByPath(ExportExtension::EXPORT_OPTION_PATH));
    }

    public function testProcessConfigsWhenGridExportIsDisabled(): void
    {
        $config = DatagridConfiguration::create(['options' => ['export' => false]]);

        $this->extension->setParameters(new ParameterBag());
        $this->extension->processConfigs($config);

        self::assertSame([], $config->offsetGetByPath(ExportExtension::EXPORT_OPTION_PATH));
    }

    public function testProcessConfigsWhenGridExportIsEnabled(): void
    {
        $config = DatagridConfiguration::create(['options' => ['export' => true]]);

        $this->extension->setParameters(new ParameterBag());
        $this->extension->processConfigs($config);

        self::assertSame(
            [
                'csv'  => [
                    'label' => 'oro.grid.export.csv (translated)'
                ],
                'xlsx' => [
                    'label'                          => 'oro.grid.export.xlsx (translated)',
                    'show_max_export_records_dialog' => true,
                    'max_export_records'             => 10000
                ]
            ],
            $config->offsetGetByPath(ExportExtension::EXPORT_OPTION_PATH)
        );
    }

    public function testProcessConfigsWhenGridExportIsEnabledForSomeFormats(): void
    {
        $config = DatagridConfiguration::create(['options' => ['export' => ['csv' => ['label' => 'some_label']]]]);

        $this->extension->setParameters(new ParameterBag());
        $this->extension->processConfigs($config);

        self::assertSame(
            [
                'csv' => [
                    'label' => 'some_label (translated)'
                ]
            ],
            $config->offsetGetByPath(ExportExtension::EXPORT_OPTION_PATH)
        );
    }
}
