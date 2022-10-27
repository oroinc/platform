<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Functional\ImportExport\EventListener;

use Doctrine\ORM\Event\PreFlushEventArgs;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\ImportExport\EventListener\FileStrategyEventListener;
use Oro\Bundle\AttachmentBundle\Model\ExternalFile;
use Oro\Bundle\DigitalAssetBundle\ImportExport\EventListener\DigitalAssetAwareFileStrategyEventListener;
use Oro\Bundle\DigitalAssetBundle\ImportExport\EventListener\DigitalAssetAwareFileStrategyPersistEventListener;
use Oro\Bundle\DigitalAssetBundle\Tests\Functional\DataFixtures\LoadUsersAvatarsDigitalAssets;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;

/**
 * @dbIsolationPerTest
 */
class DigitalAssetAwareFileStrategyEventListenerTest extends WebTestCase
{
    /** @var FileStrategyEventListener */
    private $fileStrategyListener;

    /** @var DigitalAssetAwareFileStrategyEventListener */
    private $listener;

    /** @var DigitalAssetAwareFileStrategyPersistEventListener */
    private $persistListener;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([LoadUsersAvatarsDigitalAssets::class]);

        $this->fileStrategyListener = $this->getContainer()->get(
            'oro_attachment.import_export.event_listener.file_strategy'
        );
        $this->listener = $this->getContainer()->get(
            'oro_digital_asset.import_export.event_listener.digital_asset_aware_file_strategy_event_listener'
        );
        $this->persistListener = $this->getContainer()->get(
            'oro_digital_asset.import_export.event_listener.digital_asset_aware_file_strategy_persist_event_listener'
        );
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
            $this->getContainer()->get('oro_user.importexport.strategy.user.add_or_replace'),
            $entity,
            $context
        );
    }

    public function testWhenDamNotEnabled(): void
    {
        $this->toggleDam(false);

        $fieldName = 'avatar';
        $existingUser = $this->getReference('user1');
        $user = $this->createUser($existingUser->getId());

        $file = new File();
        $symfonyFile = new SymfonyFile(
            (string) $this->getContainer()->get('kernel')
                ->locateResource('@OroUserBundle/Tests/Functional/DataFixtures/files/empty.jpg')
        );
        $file->setFile($symfonyFile);
        $user->setAvatar($file);

        $context = $this->getContext([$fieldName => []]);
        $event = $this->getEvent($context, $user);

        $this->fileStrategyListener->onProcessBefore($event);

        $existingUser->setAvatar($file);
        $event->setEntity($existingUser);

        $this->fileStrategyListener->onProcessAfter($event);
        $this->listener->onProcessAfter($event);

        self::assertEmpty($context->getErrors());
        self::assertNotEmpty($existingUser->getAvatar()->getFile());
        self::assertNotTrue($existingUser->getAvatar()->isEmptyFile());
        self::assertEmpty($existingUser->getAvatar()->getDigitalAsset());
    }

    public function testWhenFieldExcluded(): void
    {
        $this->toggleDam(true);

        $entityConfigManager = $this->getContainer()->get('oro_entity_config.config_manager');
        $fieldConfig = $entityConfigManager->getFieldConfig('importexport', User::class, 'avatar');
        $fieldConfig->set('excluded', true);

        $fieldName = 'avatar';
        $existingUser = $this->getReference('user1');
        $user = $this->createUser($existingUser->getId());

        $file = new File();
        $symfonyFile = new SymfonyFile(
            (string) $this->getContainer()->get('kernel')
                ->locateResource('@OroUserBundle/Tests/Functional/DataFixtures/files/empty.jpg')
        );
        $file->setFile($symfonyFile);
        $user->setAvatar($file);

        $context = $this->getContext([$fieldName => []]);
        $event = $this->getEvent($context, $user);

        $this->fileStrategyListener->onProcessBefore($event);

        $existingUser->setAvatar($file);
        $event->setEntity($existingUser);

        $this->fileStrategyListener->onProcessAfter($event);
        $this->listener->onProcessAfter($event);

        self::assertEmpty($context->getErrors());
        self::assertNotEmpty($existingUser->getAvatar()->getFile());
        self::assertNotTrue($existingUser->getAvatar()->isEmptyFile());
        self::assertEmpty($existingUser->getAvatar()->getDigitalAsset());
    }

    public function testWhenFileWhenReuseFromDigitalAssetSource(): void
    {
        $this->updateUserSecurityToken($this->getReference('user')->getEmail());

        $this->toggleDam(true);

        $fieldName = 'avatar';
        $digitalAsset = $this->getReference('user_2_avatar_digital_asset');
        $existingUser = $this->getReference('user1');
        $user = $this->createUser($existingUser->getId());
        $sourceFileUuid = $digitalAsset->getSourceFile()->getUuid();

        $file = new File();
        $file->setUuid($sourceFileUuid);
        $user->setAvatar($file);

        $context = $this->getContext([$fieldName => ['uuid' => $sourceFileUuid]]);
        $event = $this->getEvent($context, $user);

        self::assertNull($existingUser->getAvatar());

        $this->fileStrategyListener->onProcessBefore($event);

        $existingUser->setAvatar($file);
        $event->setEntity($existingUser);

        $this->fileStrategyListener->onProcessAfter($event);

        self::assertNotNull($existingUser->getAvatar());
        self::assertNull($existingUser->getAvatar()->getDigitalAsset());

        $this->listener->onProcessAfter($event);

        self::assertEmpty($context->getErrors());
        self::assertNotNull($existingUser->getAvatar());
        self::assertNotNull($existingUser->getAvatar()->getDigitalAsset());

        $preFlushEventArgs = new PreFlushEventArgs($this->getContainer()->get('doctrine')->getManager());
        $this->persistListener->preFlush($preFlushEventArgs);

        self::assertSame($digitalAsset, $existingUser->getAvatar()->getDigitalAsset());
    }

    private function toggleDam(bool $isEnabled, bool $isStoredExternally = false): void
    {
        $entityConfigManager = $this->getContainer()->get('oro_entity_config.config_manager');
        $avatarFieldConfig = $entityConfigManager->getFieldConfig('attachment', User::class, 'avatar');
        $avatarFieldConfig->set('use_dam', $isEnabled);
        $avatarFieldConfig->set('is_stored_externally', $isStoredExternally);
        $entityConfigManager->persist($avatarFieldConfig);
        $entityConfigManager->flush();
    }

    public function testWhenFileWhenReuseFromDigitalAssetChild(): void
    {
        $this->updateUserSecurityToken($this->getReference('user')->getEmail());

        $this->toggleDam(true);

        $fieldName = 'avatar';
        $existingUser = $this->getReference('user1');
        $user = $this->createUser($existingUser->getId());
        $user2 = $this->getReference('user2');
        $sourceFileUuid = $user2->getAvatar()->getUuid();
        $digitalAsset = $user2->getAvatar()->getDigitalAsset();

        $file = new File();
        $file->setUuid($sourceFileUuid);
        $user->setAvatar($file);

        $context = $this->getContext([$fieldName => ['uuid' => $sourceFileUuid]]);
        $event = $this->getEvent($context, $user);

        $this->fileStrategyListener->onProcessBefore($event);

        $existingUser->setAvatar(new File());
        $event->setEntity($existingUser);

        $this->fileStrategyListener->onProcessAfter($event);

        self::assertNotNull($existingUser->getAvatar());
        self::assertNull($existingUser->getAvatar()->getDigitalAsset());

        $this->listener->onProcessAfter($event);

        self::assertEmpty($context->getErrors());
        self::assertNotNull($existingUser->getAvatar());
        self::assertNotNull($existingUser->getAvatar()->getDigitalAsset());

        $preFlushEventArgs = new PreFlushEventArgs($this->getContainer()->get('doctrine')->getManager());
        $this->persistListener->preFlush($preFlushEventArgs);

        self::assertSame($digitalAsset, $existingUser->getAvatar()->getDigitalAsset());
    }

    public function testWhenFileWhenUploadDigitalAsset(): void
    {
        $this->toggleDam(true);

        $fieldName = 'avatar';
        $existingUser = $this->getReference('user1');
        $user = $this->createUser($existingUser->getId());

        $file = new File();
        $symfonyFile = new SymfonyFile(
            __DIR__ . '/../../../../../UserBundle/Tests/Functional/DataFixtures/files/empty.jpg'
        );
        $file->setFile($symfonyFile);
        $user->setAvatar($file);

        $context = $this->getContext([$fieldName => ['uuid' => '']]);
        $event = $this->getEvent($context, $user);

        $this->fileStrategyListener->onProcessBefore($event);

        $existingUser->setAvatar($file);
        $event->setEntity($existingUser);

        self::assertNotNull($existingUser->getAvatar());
        self::assertNull($existingUser->getAvatar()->getDigitalAsset());

        $this->fileStrategyListener->onProcessAfter($event);

        $this->listener->onProcessAfter($event);

        self::assertEmpty($context->getErrors());
        self::assertNotNull($existingUser->getAvatar());
        self::assertNotNull($existingUser->getAvatar()->getDigitalAsset());

        $preFlushEventArgs = new PreFlushEventArgs($this->getContainer()->get('doctrine')->getManager());
        $this->persistListener->preFlush($preFlushEventArgs);

        self::assertNotNull($existingUser->getAvatar()->getDigitalAsset());
    }

    public function testWhenFileWhenCannotReuseOrUpload(): void
    {
        $this->toggleDam(true);

        $fieldName = 'avatar';
        $existingUser = $this->getReference('user1');
        $user = $this->createUser($existingUser->getId());

        $file = new File();
        $user->setAvatar($file);

        $context = $this->getContext([$fieldName => ['uuid' => '']]);
        $event = $this->getEvent($context, $user);

        $this->fileStrategyListener->onProcessBefore($event);

        $existingUser->setAvatar($file);
        $event->setEntity($existingUser);

        self::assertNotNull($existingUser->getAvatar());
        self::assertNull($existingUser->getAvatar()->getDigitalAsset());

        $this->listener->onProcessAfter($event);

        $preFlushEventArgs = new PreFlushEventArgs($this->getContainer()->get('doctrine')->getManager());
        $this->persistListener->preFlush($preFlushEventArgs);

        self::assertNotNull($existingUser->getAvatar());
        self::assertNull($existingUser->getAvatar()->getDigitalAsset());

        self::assertEquals(
            [
                'Error in row #0. Failed to reuse digital asset in field Avatar: file not found by specified UUID (). '
                . 'Failed to create new digital asset: there is no file specified for uploading',
                'Error in row #0. Digital Asset importing has failed, entity is skipped',
            ],
            $context->getErrors()
        );
    }

    public function testWhenFileWhenCannotFindDigitalAsset(): void
    {
        $this->toggleDam(true);

        $fieldName = 'avatar';
        $existingUser = $this->getReference('user1');
        $user = $this->createUser($existingUser->getId());
        $fileWithInvalidDigitalAsset = $this->getReference('file_with_invalid_digital_asset');
        $fileUuid = $fileWithInvalidDigitalAsset->getUuid();

        $file = new File();
        $file->setUuid($fileUuid);
        $user->setAvatar($file);

        $context = $this->getContext([$fieldName => ['uuid' => $fileUuid]]);
        $event = $this->getEvent($context, $user);

        $this->fileStrategyListener->onProcessBefore($event);

        $existingUser->setAvatar($file);
        $event->setEntity($existingUser);

        self::assertNotNull($existingUser->getAvatar());
        self::assertNull($existingUser->getAvatar()->getDigitalAsset());

        $this->listener->onProcessAfter($event);

        $preFlushEventArgs = new PreFlushEventArgs($this->getContainer()->get('doctrine')->getManager());
        $this->persistListener->preFlush($preFlushEventArgs);

        self::assertNotNull($existingUser->getAvatar());
        self::assertNull($existingUser->getAvatar()->getDigitalAsset());

        self::assertEquals(
            [
                'Error in row #0. Cannot find digital asset #999999 specified as parent for file with '
                . 'UUID 07bad972-48c9-4ba9-8cb3-eb595ab2d069 in field Avatar',
                'Error in row #0. Failed to reuse digital asset in field Avatar: file not found by specified '
                . 'UUID (07bad972-48c9-4ba9-8cb3-eb595ab2d069). Failed to create new digital asset: '
                . 'there is no file specified for uploading',
                'Error in row #0. Digital Asset importing has failed, entity is skipped'
            ],
            $context->getErrors()
        );
    }

    public function testWhenExternalUrl(): void
    {
        $this->toggleDam(true);

        $existingUser = $this->getReference('user1');
        $user = $this->createUser($existingUser->getId());

        $url = 'http://example.org/sample/url/filename.jpg';
        $externalFile = new ExternalFile($url, 'filename', 100, 'image/jpeg');

        $file = new File();
        $file->setExternalUrl($url);
        $file->setFile($externalFile);
        $user->setAvatar($file);

        $context = $this->getContext(['avatar' => []]);
        $event = $this->getEvent($context, $user);

        $this->fileStrategyListener->onProcessBefore($event);

        $existingUser->setAvatar($file);
        $event->setEntity($existingUser);

        $this->listener->onProcessAfter($event);

        self::assertEquals($url, $existingUser->getAvatar()?->getExternalUrl());
        self::assertNull($existingUser->getAvatar()->getDigitalAsset());

        self::assertEmpty($context->getErrors());
    }
}
