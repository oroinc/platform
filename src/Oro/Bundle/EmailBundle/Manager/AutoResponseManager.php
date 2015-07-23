<?php

namespace Oro\Bundle\EmailBundle\Manager;

use Exception;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;

use Psr\Log\LoggerInterface;

use Oro\Bundle\EmailBundle\Entity\AutoResponseRule;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Component\ConfigExpression\ConfigExpressions;
use Oro\Bundle\EmailBundle\Builder\EmailModelBuilder;

class AutoResponseManager
{
    /** @var Registry */
    protected $registry;

    /** @var EmailModelBuilder */
    protected $emailBuilder;

    /** @var Processor */
    protected $emailProcessor;

    /** @var LoggerInterface */
    protected $logger;

    /** @var array */
    protected $filterToConditionMap = [
        TextFilterType::TYPE_CONTAINS => 'contains',
        TextFilterType::TYPE_ENDS_WITH => 'end_with',
        TextFilterType::TYPE_EQUAL => 'eq',
        TextFilterType::TYPE_IN => 'in',
        TextFilterType::TYPE_NOT_CONTAINS => 'not_contains',
        TextFilterType::TYPE_NOT_IN => 'not_in',
        TextFilterType::TYPE_STARTS_WITH => 'start_with',
        FilterUtility::TYPE_EMPTY => 'empty',
        FilterUtility::TYPE_NOT_EMPTY => 'not_empty',
    ];

    /**
     * @param Registry $registry
     * @param EmailModelBuilder $emailBuilder
     * @param Processor $emailProcessor
     * @param LoggerInterface $logger
     */
    public function __construct(
        Registry $registry,
        EmailModelBuilder $emailBuilder,
        Processor $emailProcessor,
        LoggerInterface $logger
    ) {
        $this->registry = $registry;
        $this->emailBuilder = $emailBuilder;
        $this->emailProcessor = $emailProcessor;
        $this->logger = $logger;
    }

    /**
     * @param Email $email
     */
    public function sendAutoResponses(Email $email)
    {
        $emailUsersDql = $this->getEmailUserRepository()->createQueryBuilder('ue')
            ->select('ue.id')
            ->where('ue.email = :email')
            ->andWhere('ue.mailboxOwner = m.id')
            ->setMaxResults(1)
            ->getDQL();

        $qb = $this->getMailboxRepository()->createQueryBuilder('m');
        $mailboxes = $qb
            ->select('m')
            ->andWhere($qb->expr()->exists($emailUsersDql))
            ->setParameter('email', $email)
            ->getQuery()->getResult();

        foreach ($mailboxes as $mailbox) {
             $this->processMailbox($mailbox, $email);
        }
    }

    /**
     * @param Mailbox $mailbox
     * @param Email $email
     */
    protected function processMailbox(Mailbox $mailbox, Email $email)
    {
        $rules = $this->getApplicableRules($mailbox, $email);
        $emailModels = $this->createReplyEmailModels($email, $rules);
        array_map([$this, 'sendEmailModel'], $emailModels->toArray());
    }

    /**
     * @param EmailModel $email
     */
    protected function sendEmailModel(EmailModel $email)
    {
        try {
            $this->emailProcessor->process($email);
        } catch (Exception $ex) {
            $this->logger->error('Email sending failed.', ['exception' => $ex]);
        }
    }

    /**
     * @param Email $email
     * @param AutoResponseRule[]|Collection $rules
     *
     * @return EmailModel[]|Collection
     */
    protected function createReplyEmailModels(Email $email, Collection $rules)
    {
        return $rules->map(function (AutoResponseRule $rule) use ($email) {
            $emailModel = $this->emailBuilder->createReplyEmailModel($email, true);
            $emailModel->setFrom($rule->getMailbox()->getEmail());
            $emailModel->setTo([$email->getFromEmailAddress()->getEmail()]);
            $emailModel->setContexts([$email]);
            $this->applyTemplate($emailModel, $rule->getTemplate());

            return $emailModel;
        });
    }

    /**
     * @param EmailModel $email
     * @param EmailTemplate $template
     */
    protected function applyTemplate(EmailModel $email, EmailTemplate $template)
    {
        $email
            ->setType($template->getType())
            ->setSubject($template->getSubject())
            ->setBody($template->getContent());
    }

    /**
     * @param MailBox $mailbox
     * @param Email $email
     *
     * @return AutoResponseRule[]|Collection
     */
    public function getApplicableRules(Mailbox $mailbox, Email $email)
    {
        return $mailbox->getAutoResponseRules()->filter(function (AutoResponseRule $rule) use ($email) {
            return $rule->isActive() && $this->isExprApplicable($email, $this->createRuleExpr($rule));
        });
    }

    /**
     * @param Email $email
     * @param array $expr
     *
     * @return bool
     */
    protected function isExprApplicable(Email $email, array $expr)
    {
        $language = new ConfigExpressions();

        return (bool) $language->evaluate($expr, $email);
    }

    /**
     * @param AutoResponseRule $rule
     *
     * @return array
     */
    public function createRuleExpr(AutoResponseRule $rule)
    {
        $configs = [];
        $i = 0;
        foreach ($rule->getConditions() as $condition) {
            $args = [
                sprintf('$%s', $condition->getField())
            ];
            if (!in_array($condition->getFilterType(), [FilterUtility::TYPE_EMPTY, FilterUtility::TYPE_NOT_EMPTY])) {
                $args[] = $this->parseValue($condition->getFilterValue());
            }

            $configKey = sprintf('@%s', $this->filterToConditionMap[$condition->getFilterType()]);
            $config = [
                $configKey => $args,
            ];

            if ($i > 0 && $condition->getOperation() === FilterUtility::CONDITION_OR) {
                $index = count($configs) - 1;
                $configs[$index] = [
                    '@or' => [
                        $configs[$index],
                        $config,
                    ],
                ];
            } else {
                $configs[] = $config;
            }
            $i++;
        }

        return [
            '@and' => $configs,
        ];
    }

    /**
     * @param string $value
     *
     * @return mixed
     */
    protected function parseValue($value)
    {
        $arrayTypes = [TextFilterType::TYPE_IN, TextFilterType::TYPE_NOT_IN];

        if (in_array($value, $arrayTypes)) {
            return array_map('trim', explode(',', $value));
        }

        return $value;
    }

    /**
     * @return EntityRepository
     */
    protected function getMailboxRepository()
    {
        return $this->registry->getRepository('OroEmailBundle:Mailbox');
    }

    /**
     * @return EntityRepository
     */
    protected function getEmailUserRepository()
    {
        return $this->registry->getRepository('OroEmailBundle:EmailUser');
    }
}
