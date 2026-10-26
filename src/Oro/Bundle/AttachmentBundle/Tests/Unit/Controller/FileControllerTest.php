<?php

declare(strict_types=1);

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Controller\FileController;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class FileControllerTest extends TestCase
{
    private FileManager&MockObject $fileManager;
    private ObjectManager&MockObject $objectManager;
    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private ManagerRegistry&MockObject $managerRegistry;

    #[\Override]
    protected function setUp(): void
    {
        $this->fileManager = $this->createMock(FileManager::class);
        $this->objectManager = $this->createMock(ObjectManager::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);

        $this->managerRegistry->expects(self::any())
            ->method('getManagerForClass')
            ->with(File::class)
            ->willReturn($this->objectManager);
    }

    /**
     * @dataProvider getFileDataProvider
     */
    public function testGetFile(
        string $mimeType,
        string $originalFilename,
        array $inlineMimeTypes,
        string $action,
        string $expectedContentType,
        ?string $expectedContentDisposition,
        ?string $expectedContentSecurityPolicy
    ): void {
        $file = new File();
        $file->setFilename('file.dat');
        $file->setOriginalFilename($originalFilename);
        $file->setFileSize(7);
        $file->setMimeType($mimeType);

        $this->objectManager->expects(self::once())
            ->method('find')
            ->with(File::class, 1)
            ->willReturn($file);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', self::identicalTo($file))
            ->willReturn(true);
        $this->fileManager->expects(self::once())
            ->method('getContent')
            ->with(self::identicalTo($file))
            ->willReturn('content');

        $controller = new FileController();
        $controller->setContainer(new ServiceLocator([
            'parameter_bag' => fn () => new ParameterBag(['oro_attachment.inline_mime_types' => $inlineMimeTypes]),
            'security.authorization_checker' => fn () => $this->authorizationChecker,
            ManagerRegistry::class => fn () => $this->managerRegistry,
            FileManager::class => fn () => $this->fileManager,
        ]));

        $response = $controller->getFileAction(1, 'file.dat', $action, new Request());

        self::assertSame($expectedContentType, $response->headers->get('Content-Type'));
        self::assertSame($expectedContentDisposition, $response->headers->get('Content-Disposition'));
        self::assertSame($expectedContentSecurityPolicy, $response->headers->get('Content-Security-Policy'));
    }

    public function getFileDataProvider(): array
    {
        return [
            'type from the configured list is opened inline' => [
                'mimeType' => 'video/mp4',
                'originalFilename' => 'original.dat',
                'inlineMimeTypes' => ['video/mp4'],
                'action' => FileUrlProviderInterface::FILE_ACTION_GET,
                'expectedContentType' => 'video/mp4',
                'expectedContentDisposition' => null,
                'expectedContentSecurityPolicy' => null,
            ],
            'type outside the configured list is downloaded' => [
                'mimeType' => 'image/png',
                'originalFilename' => 'original.dat',
                'inlineMimeTypes' => [],
                'action' => FileUrlProviderInterface::FILE_ACTION_GET,
                'expectedContentType' => 'image/png',
                'expectedContentDisposition' => 'attachment;filename="original.dat"',
                'expectedContentSecurityPolicy' => 'sandbox',
            ],
            'stored type that is not valid is downloaded as binary' => [
                'mimeType' => "text/plain\r\nX-Test: 1",
                'originalFilename' => 'original.dat',
                'inlineMimeTypes' => ['text/plain'],
                'action' => FileUrlProviderInterface::FILE_ACTION_GET,
                'expectedContentType' => 'application/octet-stream',
                'expectedContentDisposition' => 'attachment;filename="original.dat"',
                'expectedContentSecurityPolicy' => 'sandbox',
            ],
            'type from the configured list is downloaded when the action is download' => [
                'mimeType' => 'video/mp4',
                'originalFilename' => 'original.dat',
                'inlineMimeTypes' => ['video/mp4'],
                'action' => FileUrlProviderInterface::FILE_ACTION_DOWNLOAD,
                'expectedContentType' => 'application/force-download',
                'expectedContentDisposition' => 'attachment;filename="original.dat"',
                'expectedContentSecurityPolicy' => 'sandbox',
            ],
            'control characters in the original filename are stripped' => [
                'mimeType' => 'text/html',
                'originalFilename' => "probe\r\n.html",
                'inlineMimeTypes' => [],
                'action' => FileUrlProviderInterface::FILE_ACTION_DOWNLOAD,
                'expectedContentType' => 'application/force-download',
                'expectedContentDisposition' => 'attachment;filename="probe.html"',
                'expectedContentSecurityPolicy' => 'sandbox',
            ],
        ];
    }
}
