<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor;

use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestMagazine;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Model\TestArticleModel2;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Model\TestMagazineModel2;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class LoadTestMagazineModel2 implements ProcessorInterface
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
        /** @var GetContext $context */

        if ($context->hasResult()) {
            // data already loaded
            return;
        }

        $magazineModel = null;
        $em = $this->doctrineHelper->getEntityManagerForClass(TestMagazine::class);
        /** @var TestMagazine|null $magazineEntity */
        $magazineEntity = $em->find(TestMagazine::class, $context->getId());
        if (null !== $magazineEntity) {
            $magazineModel = new TestMagazineModel2();
            $magazineModel->setId($magazineEntity->getId());
            $magazineModel->setName($magazineEntity->getName());
            foreach ($magazineEntity->getArticles() as $articleEntity) {
                $articleModel = new TestArticleModel2();
                $articleModel->setId($articleEntity->getId());
                $articleModel->setHeadline($articleEntity->getHeadline());
                $articleModel->setBody($articleEntity->getBody());
                $magazineModel->addArticle($articleModel);
            }
            $bestArticleEntity = $magazineEntity->getBestArticle();
            if (null !== $bestArticleEntity) {
                $bestArticleModel = new TestArticleModel2();
                $bestArticleModel->setId($bestArticleEntity->getId());
                $bestArticleModel->setHeadline($bestArticleEntity->getHeadline());
                $bestArticleModel->setBody($bestArticleEntity->getBody());
                $magazineModel->setBestArticle($bestArticleModel);
            }
        }

        $context->setResult($magazineModel);
    }
}
