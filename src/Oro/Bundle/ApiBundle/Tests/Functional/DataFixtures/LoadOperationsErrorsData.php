<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ApiBundle\Batch\ErrorManager;
use Oro\Bundle\ApiBundle\Batch\Model\BatchError;
use Oro\Bundle\ApiBundle\Batch\Model\ChunkFile;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\GaufretteBundle\FileManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadOperationsErrorsData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['@OroApiBundle/Tests/Functional/DataFixtures/async_operations.yml'];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var ErrorManager $errorManager */
        $errorManager = $this->container->get('oro_api.batch.error_manager');
        /** @var FileManager $fileManager */
        $fileManager = $this->container->get('oro_api.batch.file_manager');

        $errors = [
            $this->createBatchError(
                '1-1',
                0,
                400,
                '/data/0/attributes/title',
                'not blank constraint',
                'This value should not be blank.'
            ),
            $this->createBatchError(
                '1-2',
                1,
                400,
                '/data/1/attributes/name',
                'not blank constraint',
                'This value should not be blank.'
            ),
            $this->createBatchError(
                '1-3',
                2,
                400,
                '/data/2/attributes/lastname',
                'not blank constraint',
                'This value should not be blank.'
            ),
            $this->createBatchError(
                '1-4',
                3,
                400,
                '/data/3/attributes/description',
                'not blank constraint',
                'This value should not be blank.'
            ),
            $this->createBatchError(
                '1-5',
                4,
                400,
                '/data/3/attributes/name',
                'not blank constraint',
                'This value should not be blank.'
            ),
            $this->createBatchError(
                '1-6',
                5,
                400,
                '/data/3/attributes/name',
                'not blank constraint',
                'This value should not be blank.'
            ),
            $this->createBatchError(
                '2-1',
                0,
                400,
                '/data/1/attributes/title',
                'not blank constraint',
                'This value should not be blank.'
            ),
            $this->createBatchError(
                '2-2',
                0,
                400,
                '/data/1/attributes/name',
                'not blank constraint',
                'This value should not be blank.'
            )
        ];

        $errorManager->writeErrors(
            $fileManager,
            $this->getReference('user_operation2')->getId(),
            $errors,
            new ChunkFile('test', 1, 0)
        );
    }

    protected function createBatchError(
        string $id,
        int $itemIndex,
        int $statusCode,
        string $sourcePointer,
        string $title,
        string $detail = null
    ): BatchError {
        return BatchError::create($title, $detail)
            ->setId($id)
            ->setItemIndex($itemIndex)
            ->setStatusCode($statusCode)
            ->setSource(ErrorSource::createByPointer($sourcePointer));
    }
}
