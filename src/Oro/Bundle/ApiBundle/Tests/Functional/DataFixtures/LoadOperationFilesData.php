<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ApiBundle\Batch\FileNameProvider;
use Oro\Bundle\GaufretteBundle\FileManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadOperationFilesData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['@OroApiBundle/Tests/Functional/DataFixtures/async_operations.yml'];
    }

    public function load(ObjectManager $manager)
    {
        /** @var FileManager $fileManager */
        $fileManager = $this->container->get('oro_api.batch.file_manager');

        /** @var FileNameProvider $fileNameProvider */
        $fileNameProvider = $this->container->get('oro_api.batch.file_name_provider');

        $operationIds = [
            $this->getReference('user_operation1')->getId(),
            $this->getReference('user_operation2')->getId(),
            $this->getReference('user_operation3')->getId(),
            $this->getReference('subordinate_bu_user_operation')->getId()
        ];

        foreach ($operationIds as $operationId) {
            $fileManager->writeToStorage('test', $fileNameProvider->getErrorIndexFileName($operationId));
            $fileManager->writeToStorage('test', $fileNameProvider->getInfoFileName($operationId));
        }
    }
}
