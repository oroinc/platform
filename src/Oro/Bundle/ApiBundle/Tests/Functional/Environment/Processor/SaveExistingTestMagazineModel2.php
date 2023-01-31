<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor;

use Oro\Bundle\ApiBundle\Processor\Update\SaveEntity;
use Oro\Bundle\ApiBundle\Processor\Update\UpdateContext;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestArticle;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestMagazine;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Model\TestMagazineModel2;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class SaveExistingTestMagazineModel2 implements ProcessorInterface
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
        /** @var UpdateContext $context */

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

        $form = $context->getForm();
        $em = $this->doctrineHelper->getEntityManagerForClass(TestMagazine::class);
        $articleEntityMap = new \SplObjectStorage();
        /** @var TestMagazine $magazineEntity */
        $magazineEntity = $em->find(TestMagazine::class, $context->getId());
        $magazineEntity->setName($magazineModel->getName());

        if ($form->get('articles')->isSubmitted()) {
            $oldArticleEntities = $magazineEntity->getArticles()->toArray();
            $magazineEntity->getArticles()->clear();
            foreach ($magazineModel->getArticles() as $articleModel) {
                $articleEntity = $this->findArticleEntity($oldArticleEntities, $articleModel->getId())
                    ?? new TestArticle();
                $articleEntity->setHeadline($articleModel->getHeadline());
                $articleEntity->setBody($articleModel->getBody());
                $em->persist($articleEntity);
                $articleEntityMap->attach($articleModel, $articleEntity);
                $magazineEntity->addArticle($articleEntity);
            }
        }

        if ($form->get('bestArticle')->isSubmitted()) {
            $oldBestArticleEntity = $magazineEntity->getBestArticle();
            $magazineEntity->setBestArticle(null);
            $bestArticleModel = $magazineModel->getBestArticle();
            if (null !== $bestArticleModel) {
                $bestArticleEntity = $oldBestArticleEntity ?? new TestArticle();
                $bestArticleEntity->setHeadline($bestArticleModel->getHeadline());
                $bestArticleEntity->setBody($bestArticleModel->getBody());
                $em->persist($bestArticleEntity);
                $articleEntityMap->attach($bestArticleModel, $bestArticleEntity);
                $magazineEntity->setBestArticle($bestArticleEntity);
            }
        }

        $em->flush();

        foreach ($articleEntityMap as $articleModel) {
            $articleModel->setId($articleEntityMap[$articleModel]->getId());
        }

        $context->setProcessed(SaveEntity::OPERATION_NAME);
    }

    /**
     * @param TestArticle[] $articles
     * @param int|null      $articleId
     *
     * @return TestArticle|null
     */
    private function findArticleEntity(array $articles, ?int $articleId): ?TestArticle
    {
        if (null === $articleId) {
            return null;
        }
        foreach ($articles as $article) {
            if ($article->getId() === $articleId) {
                return $article;
            }
        }

        return null;
    }
}
