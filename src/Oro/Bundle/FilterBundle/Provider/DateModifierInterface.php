<?php

namespace Oro\Bundle\FilterBundle\Provider;

/**
 * Defines constants for date variables and date parts used in date filtering.
 *
 * This interface provides a centralized definition of date-related constants that are
 * used throughout the filtering system. It includes constants for date variables
 * (such as `now`, `today`, `start of week`) and date parts (such as `day`, `month`,
 * `year`) that can be combined to create flexible date-based filter expressions.
 * These constants are used in conjunction with translation keys to provide
 * user-friendly labels for date filter options.
 */
interface DateModifierInterface
{
    public const LABEL_VAR_PREFIX  = 'oro.filter.form.label_date_var.';
    public const LABEL_PART_PREFIX = 'oro.filter.form.label_date_part.';

    public const VAR_NOW   = 1;
    public const VAR_TODAY = 2;
    public const VAR_SOW   = 3; // start of the week
    public const VAR_SOM   = 4; // start of the month
    public const VAR_SOQ   = 5; // start of the quarter
    public const VAR_SOY   = 6; // start of the year

    public const VAR_THIS_DAY       = 10;
    public const VAR_THIS_WEEK      = 11;
    public const VAR_THIS_MONTH     = 12;
    public const VAR_THIS_MONTH_W_Y = 17;
    public const VAR_THIS_QUARTER   = 13;
    public const VAR_THIS_YEAR      = 14;
    public const VAR_FDQ            = 15; // first day of quarter
    public const VAR_FMQ            = 16; // first month of quarter
    public const VAR_THIS_DAY_W_Y   = 29;

    public const PART_SOURCE   = 'source';
    public const PART_VALUE    = 'value';
    public const PART_DOW      = 'dayofweek';
    public const PART_WEEK     = 'week';
    public const PART_DAY      = 'day';
    public const PART_MONTH    = 'month';
    public const PART_QUARTER  = 'quarter';
    public const PART_DOY      = 'dayofyear';
    public const PART_YEAR     = 'year';
    public const PART_ALL_TIME = 'all_time';
}
