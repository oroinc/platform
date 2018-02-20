<?php

namespace Oro\Bundle\EmailBundle\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EmailBundle\Builder\EmailModelBuilder;
use Oro\Bundle\EmailBundle\Entity\AutoResponseRule;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Entity\Repository\MailboxRepository;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Model\AutoResponseRuleCondition;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Component\ConfigExpression\ConfigExpressions;
use Oro\Component\PhpUtils\ArrayUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class AutoResponseManager
 *
 * @package Oro\Bundle\EmailBundle\Manager
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AutoResponseManager
{
    const INDEX_PLACEHOLDER = '__index__';

    /** @var Registry */
    protected $registry;

    /** @var EmailModelBuilder */
    protected $emailBuilder;

    /** @var Processor */
    protected $emailProcessor;

    /** @var LoggerInterface */
    protected $logger;

    /** @var ConfigExpressions */
    protected $configExpressions;

    /** @var PropertyAccessorInterface */
    protected $accessor;

    /** @var string */
    protected $defaultLocale;

    /** @var EmailRenderer */
    protected $emailRender;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var array */
    protected $filterToConditionMap = [
        TextFilterType::TYPE_CONTAINS     => 'contains',
        TextFilterType::TYPE_ENDS_WITH    => 'end_with',
        TextFilterType::TYPE_EQUAL        => 'eq',
        TextFilterType::TYPE_IN           => 'in',
        TextFilterType::TYPE_NOT_CONTAINS => 'not_contains',
        TextFilterType::TYPE_NOT_IN       => 'not_in',
        TextFilterType::TYPE_STARTS_WITH  => 'start_with',
        FilterUtility::TYPE_EMPTY         => 'empty',
        FilterUtility::TYPE_NOT_EMPTY     => 'not_empty',
    ];

    /** @var array */
    protected $filterOperatorToExprMap = [
        FilterUtility::CONDITION_AND => '@and',
        FilterUtility::CONDITION_OR => '@or',
    ];

    /**
     * @param Registry            $registry
     * @param EmailModelBuilder   $emailBuilder
     * @param Processor           $emailProcessor
     * @param EmailRenderer       $emailRender
     * @param LoggerInterface     $logger
     * @param TranslatorInterface $translator
     * @param string              $defaultLocale
     */
    public function __construct(
        Registry $registry,
        EmailModelBuilder $emailBuilder,
        Processor $emailProcessor,
        EmailRenderer $emailRender,
        LoggerInterface $logger,
        TranslatorInterface $translator,
        $defaultLocale
    ) {
        $this->registry          = $registry;
        $this->emailBuilder      = $emailBuilder;
        $this->emailProcessor    = $emailProcessor;
        $this->logger            = $logger;
        $this->translator        = $translator;
        $this->defaultLocale     = $defaultLocale;
        $this->configExpressions = new ConfigExpressions();
        $this->accessor          = PropertyAccess::createPropertyAccessor();
        $this->emailRender       = $emailRender;
    }

    /**
     * @param Email $email
     *
     * @return bool
     */
    public function hasAutoResponses(Email $email)
    {
        $mailboxes = $this->getMailboxRepository()->findForEmail($email);
        foreach ($mailboxes as $mailbox) {
            if ($this->getApplicableRules($mailbox, $email)->count()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Email $email
     */
    public function sendAutoResponses(Email $email)
    {
        $mailboxes = $this->getMailboxRepository()->findForEmail($email);
        foreach ($mailboxes as $mailbox) {
            $this->processMailbox($mailbox, $email);
        }
    }

    /**
     * @return array
     */
    public function createEmailEntity()
    {
        return [
            'name' => 'email',
            'label' => $this->translator->trans('oro.email.entity_label'),
            'fields' => [
                $this->createField('subject', 'oro.email.subject.label'),
                $this->createField('emailBody.bodyContent', 'oro.email.email_body.label'),
                $this->createField('fromName', 'From'),
                $this->createField(sprintf('cc.%s.name', static::INDEX_PLACEHOLDER), 'Cc'),
                $this->createField(sprintf('bcc.%s.name', static::INDEX_PLACEHOLDER), 'Bcc'),
            ],
        ];
    }

    /**
     * @param string $name
     * @param string $label
     *
     * @return array
     */
    protected function createField($name, $label)
    {
        return [
            'label' => $this->translator->trans($label),
            'name' => $name,
            'type' => 'text',
        ];
    }

    /**
     * @param Mailbox $mailbox
     * @param Email   $email
     */
    protected function processMailbox(Mailbox $mailbox, Email $email)
    {
        $rules       = $this->getApplicableRules($mailbox, $email);
        $emailModels = $this->createReplyEmailModels($email, $rules);
        foreach ($emailModels as $emailModel) {
            $this->sendEmailModel($emailModel, $mailbox->getOrigin());
        }
    }

    /**
     * @param EmailModel  $email
     * @param EmailOrigin $origin
     */
    protected function sendEmailModel(EmailModel $email, EmailOrigin $origin = null)
    {
        try {
            $this->emailProcessor->process($email, $origin);
        } catch (\Exception $ex) {
            $this->logger->error('Email sending failed.', ['exception' => $ex]);
        }
    }

    /**
     * @param Email                         $email
     * @param AutoResponseRule[]|Collection $rules
     *
     * @return EmailModel[]|Collection
     */
    protected function createReplyEmailModels(Email $email, Collection $rules)
    {
        return $rules->map(function (AutoResponseRule $rule) use ($email) {
            $emailModel = $this->emailBuilder->createReplyEmailModel($email);
            $emailModel->setFrom($rule->getMailbox()->getEmail());
            $emailModel->setTo([$email->getFromEmailAddress()->getEmail()]);
            $emailModel->setContexts(array_merge([$email], $emailModel->getContexts()->toArray()));

            $this->applyTemplate($emailModel, $rule->getTemplate(), $email);

            return $emailModel;
        });
    }

    /**
     * @param EmailModel    $emailModel
     * @param EmailTemplate $template
     * @param Email         $email
     */
    protected function applyTemplate(EmailModel $emailModel, EmailTemplate $template, Email $email)
    {
        $locales        = array_merge($email->getAcceptedLocales(), [$this->defaultLocale]);
        $flippedLocales = array_flip($locales);

        $translatedSubjects = [];
        $translatedContents = [];
        foreach ($template->getTranslations() as $translation) {
            switch ($translation->getField()) {
                case 'content':
                    $translatedContents[$translation->getLocale()] = $translation->getContent();
                    break;
                case 'subject':
                    $translatedSubjects[$translation->getLocale()] = $translation->getContent();
                    break;
            }
        }

        $comparator = ArrayUtil::createOrderedComparator($flippedLocales);
        uksort($translatedSubjects, $comparator);
        uksort($translatedContents, $comparator);

        $validContents = array_intersect_key($translatedContents, $flippedLocales);
        $validsubjects = array_intersect_key($translatedSubjects, $flippedLocales);

        $content = reset($validContents);
        $subject = reset($validsubjects);

        $content = $content === false ? $template->getContent() : $content;
        $subject = $subject === false ? $template->getSubject() : $subject;

        $emailModel
            ->setSubject($this->emailRender->renderWithDefaultFilters($subject, ['entity' => $email]))
            ->setBody($this->emailRender->renderWithDefaultFilters($content, ['entity' => $email]))
            ->setType($template->getType());
    }

    /**
     * @param MailBox $mailbox
     * @param Email   $email
     *
     * @return AutoResponseRule[]|Collection
     */
    public function getApplicableRules(Mailbox $mailbox, Email $email)
    {
        return $mailbox->getAutoResponseRules()->filter(function (AutoResponseRule $rule) use ($email) {
            return $rule->isActive()
            && $this->isExprApplicable($email, $this->createRuleExpr($rule, $email))
            && $rule->getCreatedAt() < $email->getSentAt();
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
        return (bool)$this->configExpressions->evaluate($expr, $email);
    }

    /**
     * @param AutoResponseRule $rule
     * @param Email            $email
     *
     * @return array
     */
    public function createRuleExpr(AutoResponseRule $rule, Email $email)
    {
        $definition = json_decode($rule->getDefinition(), true);
        if (!$definition || !isset($definition['filters'])) {
            return [];
        }

        return $this->createGroupExpr([$definition['filters']], $email);
    }

    /**
     * @param array $group
     * @param Email $email
     */
    protected function createGroupExpr(array $group, Email $email)
    {
        $exprs = [];
        $operators = [];

        foreach ($group as $filter) {
            if (is_string($filter)) {
                $operators[] = $filter;
            } elseif (is_array($filter)) {
                if (array_key_exists('columnName', $filter)) {
                    $condition = AutoResponseRuleCondition::createFromFilter($filter);
                    $exprs[] = $this->createFilterExpr($condition, $email);
                } else {
                    $exprs[] = $this->createGroupExpr($filter, $email);
                }
            }
        }

        if (!$exprs) {
            return [];
        }

        if (count($exprs) - count($operators) !== 1) {
            throw new \LogicException('Number of operators have to be about 1 less than number of exprs.');
        }

        $exprOperators = array_map(function ($operator) {
            return $this->filterOperatorToExprMap[$operator];
        }, $operators);

        return array_reduce($exprs, function ($carry, $expr) use (&$exprOperators) {
            if (!$carry) {
                return $expr;
            }

            return [array_shift($exprOperators) => [$carry, $expr]];
        });
    }

    /**
     * @param AutoResponseRuleCondition $condition
     * @param Email $email
     *
     * @return array
     */
    protected function createFilterExpr(AutoResponseRuleCondition $condition, Email $email)
    {
        $paths = $this->getFieldPaths($condition->getField(), $email);

        $args = [null];
        if (!in_array($condition->getFilterType(), [FilterUtility::TYPE_EMPTY, FilterUtility::TYPE_NOT_EMPTY])) {
            $args[] = $this->parseValue($condition->getFilterValue(), $condition->getFilterType());
        }

        $configs = [];
        foreach ($paths as $path) {
            $args[0]   = $path;
            $configKey = sprintf('@%s', $this->filterToConditionMap[$condition->getFilterType()]);
            $configs[] = [
                $configKey => $args,
            ];
        }

        return $configs ? [$this->filterOperatorToExprMap[FilterUtility::CONDITION_OR] => $configs] : [];
    }

    /**
     * @param string $field
     * @param object $context
     *
     * @return string[]
     */
    protected function getFieldPaths($field, $context)
    {
        $delimiter = sprintf('.%s.', static::INDEX_PLACEHOLDER);
        if (strpos($field, $delimiter) !== false) {
            list($leftPart) = explode($delimiter, $field);
            $collection = $this->accessor->getValue($context, $leftPart);
            if (!$collection) {
                return [];
            }

            $keys = $collection->getKeys();
            if (!$keys) {
                return [];
            }

            return array_map(function ($key) use ($field) {
                return sprintf('$%s', str_replace(static::INDEX_PLACEHOLDER, $key, $field));
            }, $keys);
        }

        return [sprintf('$%s', $field)];
    }

    /**
     * @param string $value
     * @param string $type
     *
     * @return mixed
     */
    protected function parseValue($value, $type)
    {
        $arrayTypes = [TextFilterType::TYPE_IN, TextFilterType::TYPE_NOT_IN];

        if (in_array($type, $arrayTypes)) {
            return array_map('trim', explode(',', $value));
        }

        if ($value === null) {
            return '';
        }

        return $value;
    }

    /**
     * @return MailboxRepository
     */
    protected function getMailboxRepository()
    {
        return $this->registry->getRepository('OroEmailBundle:Mailbox');
    }
}
