<?php

namespace Oro\Bundle\ConfigBundle\Validator;

/**
 * Validates whether outbound connections to external hosts and ports are permitted
 * by an application-wide rules.
 *
 * The rules are provided as a single string and have the following grammar:
 *   rules    := rule (";" rule)*
 *   rule     := host [ ":" portRule ("," portRule)* ]
 *   host     := exact host name (case-insensitive) or a pattern containing one or
 *               more "*" wildcards, where each "*" matches one or more characters
 *   portRule := single port number (e.g. "443")
 *               or port range "from-to" (inclusive on both ends, e.g. "8000-8100")
 *
 * Examples of valid rules:
 *   "api.example.com"                     - allows any port on api.example.com
 *   "api.example.com:443"                 - allows only port 443 on api.example.com
 *   "api.example.com:80,443,8000-8100"    - allows port 80, 443 and the 8000-8100 range
 *   "*.example.com:443;payments.acme.com" - multiple semicolon-separated rules
 */
class OutboundConnectionValidator implements OutboundConnectionValidatorInterface
{
    private ?array $parsedRules = null;

    public function __construct(
        private readonly ?string $rules
    ) {
    }

    #[\Override]
    public function isConnectionAllowed(string $host, int $port): bool
    {
        $rules = $this->getRules();
        if (!$rules) {
            return true;
        }

        $isMatched = false;
        $host = $this->normalizeHost($host);
        foreach ($rules as [$hostRule, $portRules]) {
            if ($this->isHostMatched($host, $hostRule) && $this->isPortMatched($port, $portRules)) {
                $isMatched = true;
                break;
            }
        }

        return $isMatched;
    }

    private function normalizeHost(string $host): string
    {
        $host = mb_strtolower($host);
        // remove resource path if any
        $separatorPos = mb_strpos($host, '/');
        if (false !== $separatorPos) {
            $host = mb_substr($host, 0, $separatorPos);
        }
        // remove query parameters if any
        $separatorPos = mb_strpos($host, '?');
        if (false !== $separatorPos) {
            $host = mb_substr($host, 0, $separatorPos);
        }
        // remove fragment identifier if any
        $separatorPos = mb_strpos($host, '#');
        if (false !== $separatorPos) {
            $host = mb_substr($host, 0, $separatorPos);
        }

        return $host;
    }

    private function isHostMatched(string $host, string $hostRule): bool
    {
        if (!str_contains($hostRule, '*')) {
            return $host === $hostRule;
        }

        return preg_match(
            '/' . str_replace(preg_quote('*', '/'), '.+', preg_quote($hostRule, '/')) . '/',
            $host
        );
    }

    private function isPortMatched(int $port, ?array $portRules): bool
    {
        if (null === $portRules) {
            return true;
        }

        $isMatched = false;
        foreach ($portRules as $portRule) {
            if (\is_array($portRule)) {
                if ($port >= $portRule[0] && $port <= $portRule[1]) {
                    $isMatched = true;
                    break;
                }
            } elseif ($port === $portRule) {
                $isMatched = true;
                break;
            }
        }

        return $isMatched;
    }

    private function getRules(): array
    {
        if (null === $this->parsedRules) {
            $this->parsedRules = $this->parseRules();
        }

        return $this->parsedRules;
    }

    private function parseRules(): array
    {
        $parsedRules = [];
        $rules = trim($this->rules, ' ');
        if ($rules) {
            foreach (explode(';', $rules) as $rule) {
                $parsedRules[] = $this->parseRule(trim($rule, ' '));
            }
        }

        return $parsedRules;
    }

    private function parseRule(string $rule): array
    {
        $separatorPos = mb_strpos($rule, ':');
        if (false === $separatorPos) {
            return [mb_strtolower($rule), null];
        }

        return [
            mb_strtolower(trim(mb_substr($rule, 0, $separatorPos))),
            $this->parsePortRule(mb_substr($rule, $separatorPos + 1))
        ];
    }

    private function parsePortRule(string $portRule): ?array
    {
        $rules = [];
        foreach (explode(',', $portRule) as $rule) {
            $rule = trim($rule, ' ');
            if (!$rule) {
                continue;
            }
            if (str_contains($rule, '-')) {
                [$from, $to] = explode('-', $rule, 2);
                if (is_numeric($from) && is_numeric($to)) {
                    $rules[] = [(int)$from, (int)$to];
                }
            } elseif (is_numeric($rule)) {
                $rules[] = (int)$rule;
            }
        }

        return $rules ?: null;
    }
}
