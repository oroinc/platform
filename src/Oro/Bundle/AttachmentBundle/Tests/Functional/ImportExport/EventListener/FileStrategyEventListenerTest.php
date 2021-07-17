<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\ImportExport\EventListener;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\ImportExport\EventListener\FileStrategyEventListener;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUsersWithAvatars;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;

/**
 * @dbIsolationPerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FileStrategyEventListenerTest extends WebTestCase
{
    use EntityTrait;

    /** @var FileStrategyEventListener */
    private $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([LoadUsersWithAvatars::class]);

        $this->listener = $this->getContainer()->get('oro_attachment.import_export.event_listener.file_strategy');
    }

    public function testWhenNoFileFields(): void
    {
        $context = $this->getContext();
        $event = $this->getEvent($context, new Role());

        $this->listener->onProcessBefore($event);
        $this->listener->onProcessAfter($event);

        $this->assertEmpty($context->getErrors());
    }

    public function testWhenFileFieldWhenNoFileNoColumn(): void
    {
        $context = $this->getContext();
        $event = $this->getEvent($context, new User());

        $this->listener->onProcessBefore($event);
        $this->listener->onProcessAfter($event);

        $this->assertEmpty($context->getErrors());
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
            $this->getContainer()->get('oro_user.importexport.strategy.user.add_or_replace'),
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

        $this->assertEmpty($context->getErrors());
    }

    public function testFileFieldWhenNoFile(): void
    {
        $fieldName = 'avatar';
        $context = $this->getContext([$fieldName => []]);
        $existingUser = $this->getReference('user1');
        $user = $this->getEntity(User::class, ['id' => $existingUser->getId()]);
        $event = $this->getEvent($context, $user);

        $this->listener->onProcessBefore($event);

        $event->setEntity($existingUser);

        $this->listener->onProcessAfter($event);

        $this->assertEmpty($context->getErrors());
    }

    public function testFileFieldWhenFieldExcluded(): void
    {
        $fieldName = 'avatar';
        $context = $this->getContext([$fieldName => []]);
        $existingUser = $this->getReference('user2');
        $user = $this->getEntity(User::class, ['id' => $existingUser->getId()]);
        $event = $this->getEvent($context, $user);

        $entityConfigManager = $this->getContainer()->get('oro_entity_config.config_manager');
        $fieldConfig = $entityConfigManager->getFieldConfig('importexport', User::class, 'avatar');
        $fieldConfig->set('excluded', true);

        $this->assertNotNull($existingUser->getAvatar());

        $this->listener->onProcessBefore($event);

        $event->setEntity($existingUser);

        $this->listener->onProcessAfter($event);

        $this->assertNotNull($existingUser->getAvatar());
        $this->assertEmpty($context->getErrors());
    }

    public function testWhenFileFieldWhenExistingFileShouldBeDeleted(): void
    {
        $fieldName = 'avatar';
        $context = $this->getContext([$fieldName => ['uuid' => '', 'uri' => '']]);
        $existingUser = $this->getReference('user2');
        /** @var File $existingUserAvatar */
        $existingUserAvatar = $existingUser->getAvatar();
        $user = $this->getEntity(User::class, ['id' => $existingUser->getId()]);
        $event = $this->getEvent($context, $user);

        $this->listener->onProcessBefore($event);

        $existingUser->setAvatar(null);
        $event->setEntity($existingUser);

        $this->listener->onProcessAfter($event);

        $this->assertNull($existingUser->getAvatar());
        $this->assertTrue($existingUserAvatar->isEmptyFile());
        $this->assertEmpty($context->getErrors());
    }

    public function testWhenFileFieldWhenSameUuid(): void
    {
        $fieldName = 'avatar';
        $existingUser = $this->getReference('user2');
        $existingFileUuid = $existingUser->getAvatar()->getUuid();
        $user = $this->getEntity(User::class, ['id' => $existingUser->getId()]);

        $file = new File();
        $file->setUuid($existingFileUuid);
        $user->setAvatar($file);

        $context = $this->getContext([$fieldName => ['uuid' => $existingFileUuid]]);
        $event = $this->getEvent($context, $user);

        $this->listener->onProcessBefore($event);

        $existingUser->setAvatar($file);
        $event->setEntity($existingUser);

        $this->listener->onProcessAfter($event);

        $this->assertEmpty($context->getErrors());
        $this->assertEmpty($existingUser->getAvatar()->getFile());
        $this->assertNotTrue($existingUser->getAvatar()->isEmptyFile());
        $this->assertEquals($existingFileUuid, $existingUser->getAvatar()->getUuid());
    }

    private function setToken(User $user): void
    {
        $token = new UsernamePasswordOrganizationToken(
            $user,
            self::AUTH_PW,
            'main',
            $this->getReference('organization')
        );
        $this->client->getContainer()->get('security.token_storage')->setToken($token);
    }

    public function testWhenFileFieldWhenCloneByUuid(): void
    {
        $this->setToken($this->getReference('user'));

        $fieldName = 'avatar';
        /** @var File $existingFile */
        $existingFile = $this->getReference('user2')->getAvatar();
        $existingFileUuid = $existingFile->getUuid();
        $existingFileOriginalFilename = $existingFile->getOriginalFilename();

        $existingUser = $this->getReference('user1');
        $user = $this->getEntity(User::class, ['id' => $existingUser->getId()]);

        $file = new File();
        $file->setUuid($existingFileUuid);
        $user->setAvatar($file);

        $context = $this->getContext([$fieldName => ['uuid' => $existingFileUuid]]);
        $event = $this->getEvent($context, $user);

        $this->listener->onProcessBefore($event);

        $this->assertNotEquals($existingFileUuid, $user->getAvatar()->getUuid());

        $existingUser->setAvatar(new File());
        $event->setEntity($existingUser);

        $this->listener->onProcessAfter($event);

        $this->assertEmpty($context->getErrors());
        $this->assertNotEmpty($existingUser->getAvatar()->getFile());
        $this->assertNotTrue($existingUser->getAvatar()->isEmptyFile());
        $this->assertNotEquals($existingFileUuid, $existingUser->getAvatar()->getUuid());

        $this->getContainer()->get('oro_user.manager')->updateUser($existingUser);

        $this->assertNotEmpty(
            $existingUser->getAvatar()->getFilename(),
            'Failed asserting that file has been uploaded'
        );

        $this->assertEquals(
            $existingFileOriginalFilename,
            $existingUser->getAvatar()->getOriginalFilename(),
            'Failed asserting that original filename is taken from uploaded file'
        );
    }

    public function testWhenFileFieldWhenUpload(): void
    {
        $fieldName = 'avatar';
        $existingUser = $this->getReference('user1');
        $user = $this->getEntity(User::class, ['id' => $existingUser->getId()]);

        $file = new File();
        $symfonyFile = new SymfonyFile(
            (string) $this->getContainer()->get('kernel')
                ->locateResource('@OroUserBundle/Tests/Functional/DataFixtures/files/empty.jpg')
        );
        $file->setFile($symfonyFile);
        $user->setAvatar($file);

        $context = $this->getContext([$fieldName => []]);
        $event = $this->getEvent($context, $user);

        $this->listener->onProcessBefore($event);

        $this->assertNotEmpty($user->getAvatar());
        $this->assertNotEmpty($user->getAvatar()->getUuid());

        $existingUser->setAvatar((new File())->setUuid($file->getUuid()));
        $event->setEntity($existingUser);

        $this->listener->onProcessAfter($event);

        $this->assertEmpty($context->getErrors());
        $this->assertNotEmpty($existingUser->getAvatar()->getFile());
        $this->assertNotTrue($existingUser->getAvatar()->isEmptyFile());
        $this->assertNotEmpty($existingUser->getAvatar()->getUuid());

        $this->getContainer()->get('oro_user.manager')->updateUser($existingUser);

        $this->assertNotEmpty(
            $existingUser->getAvatar()->getFilename(),
            'Failed asserting that file has been uploaded'
        );

        $this->assertEquals(
            'empty.jpg',
            $existingUser->getAvatar()->getOriginalFilename(),
            'Failed asserting that original filename is taken from uploaded file'
        );
    }

    public function testWhenFileFieldWhenUploadSameName(): void
    {
        $fieldName = 'avatar';
        $existingUser = $this->getReference('user2');
        $user = $this->getEntity(User::class, ['id' => $existingUser->getId()]);

        $oldFilename = $existingUser->getAvatar()->getFilename();
        $oldOriginalFilename = $existingUser->getAvatar()->getOriginalFilename();

        $file = new File();
        $symfonyFile = new SymfonyFile(
            (string) $this->getContainer()->get('kernel')
                ->locateResource('@OroUserBundle/Tests/Functional/DataFixtures/files/empty.jpg')
        );
        $file->setFile($symfonyFile);
        $user->setAvatar($file);

        $context = $this->getContext([$fieldName => []]);
        $event = $this->getEvent($context, $user);

        $this->listener->onProcessBefore($event);

        $this->assertNotEmpty($user->getAvatar());
        $this->assertNotEmpty($user->getAvatar()->getUuid());

        $existingUser->setAvatar((new File())->setUuid($file->getUuid()));
        $event->setEntity($existingUser);

        $this->listener->onProcessAfter($event);

        $this->assertEmpty($context->getErrors());
        $this->assertNotEmpty($existingUser->getAvatar()->getFile());
        $this->assertNotTrue($existingUser->getAvatar()->isEmptyFile());
        $this->assertNotEmpty($existingUser->getAvatar()->getUuid());

        $this->getContainer()->get('oro_user.manager')->updateUser($existingUser);

        $this->assertNotEquals(
            $oldFilename,
            $existingUser->getAvatar()->getFilename(),
            'Failed asserting that file has been updated'
        );

        $this->assertEquals(
            $oldOriginalFilename,
            $existingUser->getAvatar()->getOriginalFilename(),
            'Failed asserting that original filename has not been changed'
        );
    }

    public function testWhenFileWhenFailedToUploadOrClone(): void
    {
        $fieldName = 'avatar';
        $existingUser = $this->getReference('user1');
        $user = $this->getEntity(User::class, ['id' => $existingUser->getId()]);

        $file = new File();
        $user->setAvatar($file);

        $context = $this->getContext([$fieldName => []]);
        $event = $this->getEvent($context, $user);

        $this->listener->onProcessBefore($event);

        $existingUser->setAvatar($user->getAvatar());
        $event->setEntity($existingUser);

        $this->listener->onProcessAfter($event);

        $this->assertEquals(
            [
                'Error in row #0. Failed to either upload or clone file from the existing one: file not found by ' .
                'specified UUID and nothing is specified for uploading. Please make sure avatar.URI and avatar.UUID ' .
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
        $user = $this->getEntity(User::class, ['id' => $existingUser->getId()]);

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

        $this->assertEquals(
            [
                'Error in row #0. Failed to upload a file from invalid/path: Failed to copy "invalid/path" '
                . 'because file does not exist.',
                'Error in row #0. File importing has failed, entity is skipped',
            ],
            $context->getErrors()
        );
    }

    public function testFileFieldWhenUploadWrongMimeType(): void
    {
        $kernel = $this->getContainer()->get('kernel');

        $file = new File();
        $file->setFile(
            new SymfonyFile(
                (string) $kernel->locateResource('@OroAttachmentBundle/Tests/Functional/DataFixtures/files/index.html')
            )
        );

        $fieldName = 'avatar';
        $context = $this->getContext([$fieldName => []]);

        $existingUser = $this->getReference('user2');
        $user = $this->getEntity(User::class, ['id' => $existingUser->getId()]);
        $user->setAvatar($file);

        $event = $this->getEvent($context, $user);

        $this->listener->onProcessBefore($event);

        $this->assertNotEmpty($user->getAvatar());
        $this->assertNotEmpty($user->getAvatar()->getUuid());

        $event->setEntity($existingUser);

        $this->listener->onProcessAfter($event);

        $this->getContainer()->get('oro_user.manager')->updateUser($existingUser);

        $this->assertEquals(
            [
                'Error in row #0. File validation failed for field Avatar: The mime type of the file is invalid ' .
                '("text/html"). Allowed mime types are "image/gif", "image/jpeg", "image/png".',
                'Error in row #0. File importing has failed, entity is skipped',
            ],
            $context->getErrors()
        );

        $this->assertEquals(
            'empty.jpg',
            $existingUser->getAvatar()->getOriginalFilename(),
            'Failed asserting that original filename was not changed'
        );
    }

    public function testWhenFileWhenFailedValidation(): void
    {
        $fieldName = 'avatar';
        $existingUser = $this->getReference('user1');
        $user = $this->getEntity(User::class, ['id' => $existingUser->getId()]);

        $file = new File();
        $symfonyFile = new SymfonyFile(
            (string) $this->getContainer()->get('kernel')
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

        $this->assertEquals(
            [
                'Error in row #0. File validation failed for field Avatar: An empty file is not allowed.',
                'Error in row #0. File importing has failed, entity is skipped',
            ],
            $context->getErrors()
        );
    }

    public function testWhenFileWhenFailedToClone(): void
    {
        $this->setToken($this->getReference('user'));

        $fieldName = 'avatar';
        $existingFileUuid = $this->getReference('user3')->getAvatar()->getUuid();
        $existingUser = $this->getReference('user1');
        $user = $this->getEntity(User::class, ['id' => $existingUser->getId()]);

        $file = new File();
        $file->setUuid($existingFileUuid);
        $user->setAvatar($file);

        $context = $this->getContext([$fieldName => ['uuid' => $existingFileUuid]]);
        $event = $this->getEvent($context, $user);

        $this->listener->onProcessBefore($event);

        $existingUser->setAvatar($user->getAvatar());
        $event->setEntity($existingUser);

        $this->listener->onProcessAfter($event);

        $this->assertEquals(
            [
                'Error in row #0. Failed to clone a file from 74d27cad-b800-4d71-833e-775d01aebeba: The file '
                . '"attachments/invalid/filepath.jpg" was not found.',
                'Error in row #0. File importing has failed, entity is skipped',
            ],
            $context->getErrors()
        );
    }

    public function testWhenFileWhenFailedToCloneWhenAccessDenied(): void
    {
        $this->setToken($this->getReference('user1'));

        $fieldName = 'avatar';
        $existingFileUuid = $this->getReference('user3')->getAvatar()->getUuid();
        $existingUser = $this->getReference('user1');
        $user = $this->getEntity(User::class, ['id' => $existingUser->getId()]);

        $file = new File();
        $file->setUuid($existingFileUuid);
        $user->setAvatar($file);

        $context = $this->getContext([$fieldName => ['uuid' => $existingFileUuid]]);
        $event = $this->getEvent($context, $user);

        $this->listener->onProcessBefore($event);

        $existingUser->setAvatar($user->getAvatar());
        $event->setEntity($existingUser);

        $this->listener->onProcessAfter($event);

        $this->assertEquals(
            [
                'Error in row #0. Failed to clone a file from 74d27cad-b800-4d71-833e-775d01aebeba: you do not have '
                . 'permission to view the file with uuid 74d27cad-b800-4d71-833e-775d01aebeba',
                'Error in row #0. File importing has failed, entity is skipped',
            ],
            $context->getErrors()
        );
    }
}
