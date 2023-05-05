<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor;

use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Bundle\ApiBundle\Processor\Create\SaveEntity;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestArticle;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestMagazine;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Model\TestMagazineModel2;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class SaveNewTestMagazineModel2 implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CreateContext $context */

        if ($context->isProcessed(SaveEntity::OPERATION_NAME)) {
            // the entity was already saved
            return;
        }

        /** @var TestMagazineModel2|null $magazineModel */
        $magazineModel = $context->getResult();
        if (!\is_object($magazineModel)) {
            // model does not exist
            return;
        }

        $em = $this->doctrineHelper->getEntityManagerForClass(TestMagazine::class);
        $articleEntityMap = new \SplObjectStorage();
        $magazineEntity = new TestMagazine();
        $magazineEntity->setName($magazineModel->getName());

        foreach ($magazineModel->getArticles() as $articleModel) {
            $articleEntity = new TestArticle();
            $articleEntity->setHeadline($articleModel->getHeadline());
            $articleEntity->setBody($articleModel->getBody());
            $em->persist($articleEntity);
            $articleEntityMap->attach($articleModel, $articleEntity);
            $magazineEntity->addArticle($articleEntity);
        }

        $bestArticleModel = $magazineModel->getBestArticle();
        if (null !== $bestArticleModel) {
            $bestArticleEntity = new TestArticle();
            $bestArticleEntity->setHeadline($bestArticleModel->getHeadline());
            $bestArticleEntity->setBody($bestArticleModel->getBody());
            $em->persist($bestArticleEntity);
            $articleEntityMap->attach($bestArticleModel, $bestArticleEntity);
            $magazineEntity->setBestArticle($bestArticleEntity);
        }

        $em->persist($magazineEntity);

        $em->flush();

        $magazineModel->setId($magazineEntity->getId());
        foreach ($articleEntityMap as $articleModel) {
            $articleModel->setId($articleEntityMap[$articleModel]->getId());
        }

        $context->setId($magazineModel->getId());

        $context->setProcessed(SaveEntity::OPERATION_NAME);
    }
}
