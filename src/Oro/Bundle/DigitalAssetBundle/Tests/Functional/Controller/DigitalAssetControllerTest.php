<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Functional\Controller;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\OrganizationBundle\Tests\Functional\OrganizationTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DigitalAssetControllerTest extends WebTestCase
{
    use OrganizationTrait;

    private const DIGITAL_ASSET_TITLE_1 = 'Digital Asset Title';
    private const DIGITAL_ASSET_TITLE_2 = 'Digital Asset Title (updated)';
    private const DIGITAL_ASSET_IMAGE_1 = 'digital_asset_image_1.jpg';
    private const DIGITAL_ASSET_IMAGE_2 = 'digital_asset_image_2.jpg';

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    public function testCreate(): DigitalAsset
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_digital_asset_create'));

        $form = $crawler->selectButton('Save')->form();

        $form['oro_digital_asset[titles][values][default]'] = self::DIGITAL_ASSET_TITLE_1;
        $form['oro_digital_asset[sourceFile][file]'] = $this->getFileForUpload(self::DIGITAL_ASSET_IMAGE_1);

        $this->client->followRedirects();
        $crawler = $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertStringContainsString('Digital Asset has been saved', $crawler->html());

        /** @var DigitalAsset $digitalAsset */
        $digitalAsset = current(
            $this->getContainer()
                ->get('doctrine')
                ->getRepository(DigitalAsset::class)
                ->findBy([], ['id' => 'DESC'], 1)
        );

        $this->assertInstanceOf(DigitalAsset::class, $digitalAsset);
        $this->assertEquals(self::DIGITAL_ASSET_TITLE_1, (string)$digitalAsset->getTitle());

        $this->assertInstanceOf(File::class, $digitalAsset->getSourceFile());
        $this->assertEquals(self::DIGITAL_ASSET_IMAGE_1, $digitalAsset->getSourceFile()->getOriginalFilename());

        return $digitalAsset;
    }

    private function getFileForUpload(string $name): UploadedFile
    {
        $fileLocator = $this->getContainer()->get('file_locator');
        $image = $fileLocator->locate('@OroDigitalAssetBundle/Tests/Functional/DataFixtures/files/' . $name);

        return new UploadedFile($image, $name, 'image/jpeg');
    }

    /**
     * @depends testCreate
     */
    public function testUpdate(DigitalAsset $digitalAsset): DigitalAsset
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_digital_asset_update', ['id' => $digitalAsset->getId()])
        );

        $form = $crawler->selectButton('Save')->form();

        $form['oro_digital_asset[titles][values][default]'] = self::DIGITAL_ASSET_TITLE_2;
        $form['oro_digital_asset[sourceFile][file]'] = $this->getFileForUpload(self::DIGITAL_ASSET_IMAGE_2);

        $this->client->followRedirects();
        $crawler = $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertStringContainsString('Digital Asset has been saved', $crawler->html());

        /** @var DigitalAsset $digitalAsset */
        $digitalAsset = current(
            $this->getContainer()
                ->get('doctrine')
                ->getRepository(DigitalAsset::class)
                ->findBy([], ['id' => 'DESC'], 1)
        );

        $this->assertInstanceOf(DigitalAsset::class, $digitalAsset);
        $this->assertEquals(self::DIGITAL_ASSET_TITLE_2, (string)$digitalAsset->getTitle());

        $this->assertInstanceOf(File::class, $digitalAsset->getSourceFile());
        $this->assertEquals(self::DIGITAL_ASSET_IMAGE_2, $digitalAsset->getSourceFile()->getOriginalFilename());

        return $digitalAsset;
    }

    public function testIndex(): void
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_digital_asset_index'));
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertStringContainsString('digital-asset-grid', $crawler->html());
        static::assertStringContainsString(
            'Create Digital Asset',
            $crawler->filter('div.title-buttons-container')->html()
        );
    }

    /**
     * @depends testUpdate
     */
    public function testGrid(DigitalAsset $digitalAsset): void
    {
        $response = $this->client->requestGrid('digital-asset-grid');
        $result = $this->getJsonResponseContent($response, 200);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertCount($this->getDigitalAssetCount(), $result['data']);
        $data = reset($result['data']);
        $this->assertArrayHasKey('id', $data);
        $this->assertEquals($digitalAsset->getId(), $data['id']);
        $this->assertArrayHasKey('title', $data);
        $this->assertEquals($digitalAsset->getTitle(), $data['title']);
    }

    private function getDigitalAssetCount(): int
    {
        $qb = $this->getContainer()->get('doctrine')
            ->getManagerForClass(DigitalAsset::class)
            ->getRepository(DigitalAsset::class)
            ->createQueryBuilder('da');
        $qb
            ->select($qb->expr()->count('da'))
            ->where($qb->expr()->eq('da.organization', ':organization'))
            ->setParameter('organization', $this->getOrganization());

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function testChooseUploadWhenInvalidEntityClass(): void
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_digital_asset_widget_choose',
                [
                    'parentEntityClass' => 'InvalidClass',
                    'parentEntityFieldName' => 'sampleField',
                ]
            )
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 404);
    }

    public function testChooseUploadWhenNotConfigurableField(): void
    {
        $entityClassNameHelper = $this->getContainer()->get('oro_entity.entity_class_name_helper');
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_digital_asset_widget_choose',
                [
                    'parentEntityClass' => $entityClassNameHelper->getUrlSafeClassName(User::class),
                    'parentEntityFieldName' => 'sampleField',
                ]
            )
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 404);
    }

    public function testChooseUpload(): DigitalAsset
    {
        $entityClassNameHelper = $this->getContainer()->get('oro_entity.entity_class_name_helper');
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_digital_asset_widget_choose',
                [
                    'parentEntityClass' => $entityClassNameHelper->getUrlSafeClassName(User::class),
                    'parentEntityFieldName' => 'avatar',
                ]
            )
        );

        $form = $crawler->selectButton('Upload')->form();

        $form['oro_digital_asset_in_dialog[titles][values][default]'] = self::DIGITAL_ASSET_TITLE_1;
        $form['oro_digital_asset_in_dialog[sourceFile][file]'] = $this->getFileForUpload(self::DIGITAL_ASSET_IMAGE_1);

        $this->client->followRedirects();
        $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        /** @var DigitalAsset $digitalAsset */
        $digitalAsset = current(
            $this->getContainer()
                ->get('doctrine')
                ->getRepository(DigitalAsset::class)
                ->findBy([], ['id' => 'DESC'], 1)
        );

        $this->assertInstanceOf(DigitalAsset::class, $digitalAsset);
        $this->assertEquals(self::DIGITAL_ASSET_TITLE_1, (string)$digitalAsset->getTitle());

        $this->assertInstanceOf(File::class, $digitalAsset->getSourceFile());
        $this->assertEquals(self::DIGITAL_ASSET_IMAGE_1, $digitalAsset->getSourceFile()->getOriginalFilename());

        return $digitalAsset;
    }

    /**
     * @depends testChooseUpload
     */
    public function testChoose(DigitalAsset $digitalAsset): void
    {
        // Enables DAM for user avatar field. Will be disabled in ::tearDownAfterClass().
        $this->toggleDam(true);

        $userId = $digitalAsset->getOwner()->getId();
        $user = $this->getUserById($userId);

        $this->assertInstanceOf(File::class, $user->getAvatar());
        $this->assertNotEquals(self::DIGITAL_ASSET_IMAGE_1, $user->getAvatar()->getOriginalFilename());

        $crawler = $this->client->request('GET', $this->getUrl('oro_user_update', ['id' => $userId]));

        $form = $crawler->selectButton('Save')->form();

        $this->assertArrayHasKey('oro_user_user_form[avatar][digitalAsset]', $form);
        $form['oro_user_user_form[avatar][digitalAsset]'] = $digitalAsset->getId();

        $this->client->followRedirects();
        $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertEquals(
            self::DIGITAL_ASSET_IMAGE_1,
            $this->getUserById($userId)->getAvatar()->getOriginalFilename()
        );
    }

    public function testChooseImageUpload(): void
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_digital_asset_widget_choose_image'));

        $form = $crawler->selectButton('Upload')->form();

        $form['oro_digital_asset_in_dialog[titles][values][default]'] = self::DIGITAL_ASSET_TITLE_1;
        $form['oro_digital_asset_in_dialog[sourceFile][file]'] = $this->getFileForUpload(self::DIGITAL_ASSET_IMAGE_1);

        $this->client->followRedirects();
        $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        /** @var DigitalAsset $digitalAsset */
        $digitalAsset = current(
            $this->getContainer()
                ->get('doctrine')
                ->getRepository(DigitalAsset::class)
                ->findBy([], ['id' => 'DESC'], 1)
        );

        $this->assertInstanceOf(DigitalAsset::class, $digitalAsset);
        $this->assertEquals(self::DIGITAL_ASSET_TITLE_1, (string)$digitalAsset->getTitle());

        $this->assertInstanceOf(File::class, $digitalAsset->getSourceFile());
        $this->assertEquals(self::DIGITAL_ASSET_IMAGE_1, $digitalAsset->getSourceFile()->getOriginalFilename());
    }

    public function testChooseFileUpload(): void
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_digital_asset_widget_choose_file'));

        $form = $crawler->selectButton('Upload')->form();

        $form['oro_digital_asset_in_dialog[titles][values][default]'] = self::DIGITAL_ASSET_TITLE_2;
        $form['oro_digital_asset_in_dialog[sourceFile][file]'] = $this->getFileForUpload(self::DIGITAL_ASSET_IMAGE_2);

        $this->client->followRedirects();
        $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        /** @var DigitalAsset $digitalAsset */
        $digitalAsset = current(
            $this->getContainer()
                ->get('doctrine')
                ->getRepository(DigitalAsset::class)
                ->findBy([], ['id' => 'DESC'], 1)
        );

        $this->assertInstanceOf(DigitalAsset::class, $digitalAsset);
        $this->assertEquals(self::DIGITAL_ASSET_TITLE_2, (string)$digitalAsset->getTitle());

        $this->assertInstanceOf(File::class, $digitalAsset->getSourceFile());
        $this->assertEquals(self::DIGITAL_ASSET_IMAGE_2, $digitalAsset->getSourceFile()->getOriginalFilename());
    }

    private function getUserById(int $id): User
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(User::class)->find(User::class, $id);
    }

    /**
     * {@inheritdoc}
     */
    public static function tearDownAfterClass(): void
    {
        // Disables DAM for user avatar field.
        self::toggleDam(false);
    }

    /**
     * Enables/disables DAM for avatar field on user form.
     */
    private static function toggleDam(bool $state): void
    {
        $entityConfigManager = self::getContainer()->get('oro_entity_config.config_manager');
        $attachmentFieldConfig = $entityConfigManager->getFieldConfig('attachment', User::class, 'avatar');
        $attachmentFieldConfig->set('use_dam', $state);
        $entityConfigManager->persist($attachmentFieldConfig);
        $entityConfigManager->flush();
    }
}
