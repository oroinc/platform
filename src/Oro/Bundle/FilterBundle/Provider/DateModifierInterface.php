<?php

namespace Oro\Bundle\FilterBundle\Provider;

interface DateModifierInterface
{
    const LABEL_VAR_PREFIX  = 'oro.filter.form.label_date_var.';
    const LABEL_PART_PREFIX = 'oro.filter.form.label_date_part.';

    const VAR_NOW   = 1;
    const VAR_TODAY = 2;
    const VAR_SOW   = 3; // start of the week
    const VAR_SOM   = 4; // start of the month
    const VAR_SOQ   = 5; // start of the quarter
    const VAR_SOY   = 6; // start of the year

    const VAR_THIS_DAY       = 10;
    const VAR_THIS_WEEK      = 11;
    const VAR_THIS_MONTH     = 12;
    const VAR_THIS_MONTH_W_Y = 17;
    const VAR_THIS_QUARTER   = 13;
    const VAR_THIS_YEAR      = 14;
    const VAR_FDQ            = 15; // first day of quarter
    const VAR_FMQ            = 16; // first month of quarter
    const VAR_THIS_DAY_W_Y   = 29;

    const PART_SOURCE   = 'source';
    const PART_VALUE    = 'value';
    const PART_DOW      = 'dayofweek';
    const PART_WEEK     = 'week';
    const PART_DAY      = 'day';
    const PART_MONTH    = 'month';
    const PART_QUARTER  = 'quarter';
    const PART_DOY      = 'dayofyear';
    const PART_YEAR     = 'year';
    const PART_ALL_TIME = 'all_time';
}
