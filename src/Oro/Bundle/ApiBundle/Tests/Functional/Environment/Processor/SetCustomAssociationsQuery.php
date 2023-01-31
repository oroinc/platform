<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestCustomArticle;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestCustomMagazine;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class SetCustomAssociationsQuery implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();

        $customArticlesField = $definition->getField('customArticles');
        if (null !== $customArticlesField
            && !$customArticlesField->isExcluded()
            && null === $customArticlesField->getAssociationQuery()
        ) {
            $customArticlesField->setAssociationQuery($this->doctrineHelper
                ->createQueryBuilder(TestCustomArticle::class, 'r')
                ->innerJoin(TestCustomMagazine::class, 'e', Join::WITH, 'r MEMBER OF e.articles'));
        }

        $customBestArticleField = $definition->getField('customBestArticle');
        if (null !== $customBestArticleField
            && !$customBestArticleField->isExcluded()
            && null === $customBestArticleField->getAssociationQuery()
        ) {
            $customBestArticleField->setAssociationQuery($this->doctrineHelper
                ->createQueryBuilder(TestCustomArticle::class, 'r')
                ->innerJoin(TestCustomMagazine::class, 'e', Join::WITH, 'e.bestArticle = r'));
        }
    }
}
