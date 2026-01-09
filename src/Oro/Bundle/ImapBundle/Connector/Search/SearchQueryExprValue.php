<?php

namespace Oro\Bundle\ImapBundle\Connector\Search;

/**
 * Represents a value-based search criterion in an IMAP search query expression.
 *
 * This class encapsulates a search value that is applied across all message properties
 * without targeting a specific field. It allows searching for word phrases or complex
 * nested expressions across the entire message content.
 */
class SearchQueryExprValue extends SearchQueryExprValueBase implements
    SearchQueryExprValueInterface,
    SearchQueryExprInterface
{
}
