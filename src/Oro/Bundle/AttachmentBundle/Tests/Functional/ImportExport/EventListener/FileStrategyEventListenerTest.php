<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\ImportExport\EventListener;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\ImportExport\EventListener\FileStrategyEventListener;
use Oro\Bundle\AttachmentBundle\Model\ExternalFile;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUsersWithAvatars;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FileStrategyEventListenerTest extends WebTestCase
{
    private FileStrategyEventListener $listener;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([LoadUsersWithAvatars::class]);

        $this->listener = self::getContainer()->get('oro_attachment.import_export.event_listener.file_strategy');
    }

    private function createUser(int $id): User
    {
        $user = new User();
        ReflectionUtil::setId($user, $id);

        return $user;
    }

    public function testWhenNoFileFields(): void
    {
        $context = $this->getContext();
        $event = $this->getEvent($context, new Role());

        $this->listener->onProcessBefore($event);
        $this->listener->onProcessAfter($event);

        self::assertEmpty($context->getErrors());
    }

    public function testWhenFileFieldWhenNoFileNoColumn(): void
    {
        $context = $this->getContext();
        $event = $this->getEvent($context, new User());

        $this->listener->onProcessBefore($event);
        $this->listener->onProcessAfter($event);

        self::assertEmpty($context->getErrors());
    }

    private function getContext(array $itemData = []): Context
    {
        $context = new Context([]);
        $context->setValue('itemData', $itemData);

        return $context;
    }

    private function getEvent(Context $context, object $entity): StrategyEvent
    {
        return new StrategyEvent(
            self::getContainer()->get('oro_user.importexport.strategy.user.add_or_replace'),
            $entity,
            $context
        );
    }

    public function testFileFieldWhenNoFileNoExistingEntity(): void
    {
        $fieldName = 'avatar';
        $context = $this->getContext([$fieldName => []]);
        $event = $this->getEvent($context, new User());

        $this->listener->onProcessBefore($event);
        $this->listener->onProcessAfter($event);

        self::assertEmpty($context->getErrors());
    }

    public function testFileFieldWhenNoFile(): void
    {
        $fieldName = 'avatar';
        $context = $this->getContext([$fieldName => []]);
        $existingUser = $this->getReference('user1');
        $user = $this->createUser($existingUser->getId());
        $event = $this->getEvent($context, $user);

        $this->listener->onProcessBefore($event);

        $event->setEntity($existingUser);

        $this->listener->onProcessAfter($event);

        self::assertEmpty($context->getErrors());
    }

    public function testFileFieldWhenFieldExcluded(): void
    {
        $fieldName = 'avatar';
        $context = $this->getContext([$fieldName => []]);
        $existingUser = $this->getReference('user2');
        $user = $this->createUser($existingUser->getId());
        $event = $this->getEvent($context, $user);

        $entityConfigManager = self::getContainer()->get('oro_entity_config.config_manager');
        $fieldConfig = $entityConfigManager->getFieldConfig('importexport', User::class, 'avatar');
        $fieldConfig->set('excluded', true);

        self::assertNotNull($existingUser->getAvatar());

        $this->listener->onProcessBefore($event);

        $event->setEntity($existingUser);

        $this->listener->onProcessAfter($event);

        self::assertNotNull($existingUser->getAvatar());
        self::assertEmpty($context->getErrors());
    }

    public function testWhenFileFieldWhenExistingFileShouldBeDeleted(): void
    {
        $fieldName = 'avatar';
        $context = $this->getContext([$fieldName => ['uuid' => '', 'uri' => '']]);
        $existingUser = $this->getReference('user2');
        /** @var File $existingUserAvatar */
        $existingUserAvatar = $existingUser->getAvatar();
        $user = $this->createUser($existingUser->getId());
        $event = $this->getEvent($context, $user);

        $this->listener->onProcessBefore($event);

        $existingUser->setAvatar(null);
        $event->setEntity($existingUser);

        $this->listener->onProcessAfter($event);

        self::assertNull($existingUser->getAvatar());
        self::assertTrue($existingUserAvatar->isEmptyFile());
        self::assertEmpty($context->getErrors());
    }

    public function testWhenFileFieldWhenSameUuid(): void
    {
        $fieldName = 'avatar';
        $existingUser = $this->getReference('user2');
        $existingFileUuid = $existingUser->getAvatar()->getUuid();
        $user = $this->createUser($existingUser->getId());

        $file = new File();
        $file->setUuid($existingFileUuid);
        $user->setAvatar($file);

        $context = $this->getContext([$fieldName => ['uuid' => $existingFileUuid]]);
        $event = $this->getEvent($context, $user);

        $this->listener->onProcessBefore($event);

        $existingUser->setAvatar($file);
        $event->setEntity($existingUser);

        $this->listener->onProcessAfter($event);

        self::assertEmpty($context->getErrors());
        self::assertEmpty($existingUser->getAvatar()->getFile());
        self::assertNotTrue($existingUser->getAvatar()->isEmptyFile());
        self::assertEquals($existingFileUuid, $existingUser->getAvatar()->getUuid());
    }

    public function testWhenFileFieldWhenCloneByUuid(): void
    {
        $this->updateUserSecurityToken($this->getReference('user')->getEmail());

        $fieldName = 'avatar';
        /** @var File $existingFile */
        $existingFile = $this->getReference('user2')->getAvatar();
        $existingFileUuid = $existingFile->getUuid();
        $existingFileOriginalFilename = $existingFile->getOriginalFilename();

        $existingUser = $this->getReference('user1');
        $user = $this->createUser($existingUser->getId());

        $file = new File();
        $file->setUuid($existingFileUuid);
        $user->setAvatar($file);

        $context = $this->getContext([$fieldName => ['uuid' => $existingFileUuid]]);
        $event = $this->getEvent($context, $user);

        $this->listener->onProcessBefore($event);

        self::assertNotEquals($existingFileUuid, $user->getAvatar()->getUuid());

        $existingUser->setAvatar(new File());
        $event->setEntity($existingUser);

        $this->listener->onProcessAfter($event);

        self::assertEmpty($context->getErrors());
        self::assertNotEmpty($existingUser->getAvatar()->getFile());
        self::assertNotTrue($existingUser->getAvatar()->isEmptyFile());
        self::assertNotEquals($existingFileUuid, $existingUser->getAvatar()->getUuid());

        self::getContainer()->get('oro_user.manager')->updateUser($existingUser);

        self::assertNotEmpty(
            $existingUser->getAvatar()->getFilename(),
            'Failed asserting that file has been uploaded'
        );

        self::assertEquals(
            $existingFileOriginalFilename,
            $existingUser->getAvatar()->getOriginalFilename(),
            'Failed asserting that original filename is taken from uploaded file'
        );
    }

    public function testWhenFileFieldWhenUpload(): void
    {
        $fieldName = 'avatar';
        $existingUser = $this->getReference('user1');
        $user = $this->createUser($existingUser->getId());

        $file = new File();
        $symfonyFile = new SymfonyFile(
            (string) self::getContainer()->get('kernel')
                ->locateResource('@OroUserBundle/Tests/Functional/DataFixtures/files/empty.jpg')
        );
        $file->setFile($symfonyFile);
        $user->setAvatar($file);

        $context = $this->getContext([$fieldName => []]);
        $event = $this->getEvent($context, $user);

        $this->listener->onProcessBefore($event);

        self::assertNotEmpty($user->getAvatar());
        self::assertNotEmpty($user->getAvatar()->getUuid());

        $existingUser->setAvatar((new File())->setUuid($file->getUuid()));
        $event->setEntity($existingUser);

        $this->listener->onProcessAfter($event);

        self::assertEmpty($context->getErrors());
        self::assertNotEmpty($existingUser->getAvatar()->getFile());
        self::assertNotTrue($existingUser->getAvatar()->isEmptyFile());
        self::assertNotEmpty($existingUser->getAvatar()->getUuid());

        self::getContainer()->get('oro_user.manager')->updateUser($existingUser);

        self::assertNotEmpty(
            $existingUser->getAvatar()->getFilename(),
            'Failed asserting that file has been uploaded'
        );

        self::assertEquals(
            'empty.jpg',
            $existingUser->getAvatar()->getOriginalFilename(),
            'Failed asserting that original filename is taken from uploaded file'
        );
    }

    public function testWhenFileFieldWhenUploadSameName(): void
    {
        $fieldName = 'avatar';
        $existingUser = $this->getReference('user2');
        $user = $this->createUser($existingUser->getId());

        $oldFilename = $existingUser->getAvatar()->getFilename();
        $oldOriginalFilename = $existingUser->getAvatar()->getOriginalFilename();

        $file = new File();
        $symfonyFile = new SymfonyFile(
            (string) self::getContainer()->get('kernel')
                ->locateResource('@OroUserBundle/Tests/Functional/DataFixtures/files/empty.jpg')
        );
        $file->setFile($symfonyFile);
        $user->setAvatar($file);

        $context = $this->getContext([$fieldName => []]);
        $event = $this->getEvent($context, $user);

        $this->listener->onProcessBefore($event);

        self::assertNotEmpty($user->getAvatar());
        self::assertNotEmpty($user->getAvatar()->getUuid());

        $existingUser->setAvatar((new File())->setUuid($file->getUuid()));
        $event->setEntity($existingUser);

        $this->listener->onProcessAfter($event);

        self::assertEmpty($context->getErrors());
        self::assertNotEmpty($existingUser->getAvatar()->getFile());
        self::assertNotTrue($existingUser->getAvatar()->isEmptyFile());
        self::assertNotEmpty($existingUser->getAvatar()->getUuid());

        self::getContainer()->get('oro_user.manager')->updateUser($existingUser);

        self::assertNotEquals(
            $oldFilename,
            $existingUser->getAvatar()->getFilename(),
            'Failed asserting that file has been updated'
        );

        self::assertEquals(
            $oldOriginalFilename,
            $existingUser->getAvatar()->getOriginalFilename(),
            'Failed asserting that original filename has not been changed'
        );
    }

    public function testWhenFileWhenFailedToUploadOrClone(): void
    {
        $fieldName = 'avatar';
        $existingUser = $this->getReference('user1');
        $user = $this->createUser($existingUser->getId());

        $file = new File();
        $user->setAvatar($file);

        $context = $this->getContext([$fieldName => []]);
        $event = $this->getEvent($context, $user);

        $this->listener->onProcessBefore($event);

        $existingUser->setAvatar($user->getAvatar());
        $event->setEntity($existingUser);

        $this->listener->onProcessAfter($event);

        self::assertEquals(
            [
                'Error in row #0. Failed to either upload or clone file from the existing one: file not found by ' .
                'specified UUID and nothing is specified for uploading. Please make sure Avatar.URI and Avatar.UUID ' .
                'columns are present in the import file and have the correct values.',
                'Error in row #0. File importing has failed, entity is skipped',
            ],
            $context->getErrors()
        );
    }

    public function testWhenFileWhenFailedToUpload(): void
    {
        $fieldName = 'avatar';
        $existingUser = $this->getReference('user1');
        $user = $this->createUser($existingUser->getId());

        $file = new File();
        $symfonyFile = new SymfonyFile('invalid/path', false);
        $file->setFile($symfonyFile);
        $user->setAvatar($file);

        $context = $this->getContext([$fieldName => []]);
        $event = $this->getEvent($context, $user);

        $this->listener->onProcessBefore($event);

        $existingUser->setAvatar($user->getAvatar());
        $event->setEntity($existingUser);

        $this->listener->onProcessAfter($event);

        self::assertEquals(
            [
                'Error in row #0. Failed to upload a file from invalid/path for field Avatar: '
                . 'Failed to copy "invalid/path" because file does not exist.',
                'Error in row #0. File importing has failed, entity is skipped',
            ],
            $context->getErrors()
        );
    }

    public function testFileFieldWhenUploadWrongMimeType(): void
    {
        $kernel = self::getContainer()->get('kernel');

        $file = new File();
        $file->setFile(
            new SymfonyFile(
                (string) $kernel->locateResource('@OroAttachmentBundle/Tests/Functional/DataFixtures/files/index.html')
            )
        );

        $fieldName = 'avatar';
        $context = $this->getContext([$fieldName => []]);

        $existingUser = $this->getReference('user2');
        $user = $this->createUser($existingUser->getId());
        $user->setAvatar($file);

        $event = $this->getEvent($context, $user);

        $this->listener->onProcessBefore($event);

        self::assertNotEmpty($user->getAvatar());
        self::assertNotEmpty($user->getAvatar()->getUuid());

        $event->setEntity($existingUser);

        $this->listener->onProcessAfter($event);

        self::getContainer()->get('oro_user.manager')->updateUser($existingUser);

        self::assertEquals(
            [
                'Error in row #0. File validation failed for field Avatar: The MIME type of the file is invalid ' .
                '("text/html"). Allowed MIME types are "image/gif", "image/jpeg", "image/png", "image/webp".',
                'Error in row #0. File importing has failed, entity is skipped',
            ],
            $context->getErrors()
        );

        self::assertEquals(
            'empty.jpg',
            $existingUser->getAvatar()->getOriginalFilename(),
            'Failed asserting that original filename was not changed'
        );
    }

    public function testWhenFileWhenFailedValidation(): void
    {
        $fieldName = 'avatar';
        $existingUser = $this->getReference('user1');
        $user = $this->createUser($existingUser->getId());

        $file = new File();
        $symfonyFile = new SymfonyFile(
            (string) self::getContainer()->get('kernel')
                ->locateResource('@OroAttachmentBundle/Tests/Functional/DataFixtures/files/invalid.extension')
        );
        $file->setFile($symfonyFile);
        $user->setAvatar($file);

        $context = $this->getContext([$fieldName => []]);
        $event = $this->getEvent($context, $user);

        $this->listener->onProcessBefore($event);

        $existingUser->setAvatar($user->getAvatar());
        $event->setEntity($existingUser);

        $this->listener->onProcessAfter($event);

        self::assertEquals(
            [
                'Error in row #0. File validation failed for field Avatar: An empty file is not allowed.',
                'Error in row #0. File importing has failed, entity is skipped',
            ],
            $context->getErrors()
        );
    }

    public function testWhenFileWhenFailedToClone(): void
    {
        $this->updateUserSecurityToken($this->getReference('user')->getEmail());

        $fieldName = 'avatar';
        $existingFileUuid = $this->getReference('user3')->getAvatar()->getUuid();
        $existingUser = $this->getReference('user1');
        $user = $this->createUser($existingUser->getId());

        $file = new File();
        $file->setUuid($existingFileUuid);
        $user->setAvatar($file);

        $context = $this->getContext([$fieldName => ['uuid' => $existingFileUuid]]);
        $event = $this->getEvent($context, $user);

        $this->listener->onProcessBefore($event);

        $existingUser->setAvatar($user->getAvatar());
        $event->setEntity($existingUser);

        $this->listener->onProcessAfter($event);

        self::assertEquals(
            [
                'Error in row #0. Failed to clone a file from 74d27cad-b800-4d71-833e-775d01aebeba for field Avatar: '
                . 'The file "attachments/invalid/filepath.jpg" was not found.',
                'Error in row #0. File importing has failed, entity is skipped',
            ],
            $context->getErrors()
        );
    }

    public function testWhenFileWhenFailedToCloneWhenAccessDenied(): void
    {
        $this->updateUserSecurityToken($this->getReference('user1')->getEmail());

        $fieldName = 'avatar';
        $existingFileUuid = $this->getReference('user3')->getAvatar()->getUuid();
        $existingUser = $this->getReference('user1');
        $user = $this->createUser($existingUser->getId());

        $file = new File();
        $file->setUuid($existingFileUuid);
        $user->setAvatar($file);

        $context = $this->getContext([$fieldName => ['uuid' => $existingFileUuid]]);
        $event = $this->getEvent($context, $user);

        $this->listener->onProcessBefore($event);

        $existingUser->setAvatar($user->getAvatar());
        $event->setEntity($existingUser);

        $this->listener->onProcessAfter($event);

        self::assertEquals(
            [
                'Error in row #0. Failed to clone a file from 74d27cad-b800-4d71-833e-775d01aebeba for field Avatar: '
                . 'you do not have permission to view the file with uuid 74d27cad-b800-4d71-833e-775d01aebeba',
                'Error in row #0. File importing has failed, entity is skipped',
            ],
            $context->getErrors()
        );
    }

    public function testFileFieldWhenExternalUrl(): void
    {
        $this->markTestSkipped('Due to BAP-21155');

        $context = $this->getContext(['avatar' => []]);

        $url = 'http://example.org/sample/url/filename.jpg';
        $externalFile = new ExternalFile($url, 'filename', 100, 'image/jpeg');

        $file = new File();
        $file->setFile($externalFile);

        $existingUser = $this->getReference('user1');
        $user = $this->createUser($existingUser->getId());
        $user->setAvatar($file);

        $event = $this->getEvent($context, $user);

        $this->listener->onProcessBefore($event);
        $this->listener->onProcessAfter($event);

        self::assertEmpty($context->getErrors());
    }

    public function testFileFieldWhenExternalUrlWithWrongMimeType(): void
    {
        $this->markTestSkipped('Due to BAP-21155');

        $url = 'http://example.org/sample/url/filename.pdf';
        $externalFile = new ExternalFile($url, 'filename', 100, 'application/pdf');

        $file = new File();
        $file->setFile($externalFile);

        $fieldName = 'avatar';
        $context = $this->getContext([$fieldName => []]);

        $existingUser = $this->getReference('user1');
        $user = $this->createUser($existingUser->getId());
        $user->setAvatar($file);

        $event = $this->getEvent($context, $user);

        $this->listener->onProcessBefore($event);
        $this->listener->onProcessAfter($event);

        self::assertEquals(
            [
                'Error in row #0. File validation failed for field Avatar: The MIME type of the file is invalid ' .
                '("text/html"). Allowed MIME types are "image/gif", "image/jpeg", "image/png", "image/webp".',
                'Error in row #0. File importing has failed, entity is skipped',
            ],
            $context->getErrors()
        );

        self::assertEquals(
            'empty.jpg',
            $existingUser->getAvatar()->getOriginalFilename(),
            'Failed asserting that original filename was not changed'
        );
    }
}
