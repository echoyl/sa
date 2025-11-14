<?php

namespace Echoyl\Sa\Services\utils;

class DateService
{
    /**
     * 获取当天时间段
     *
     * @return string[]
     */
    public static function rangeToday()
    {
        $today = date('Y-m-d');

        return [$today.' 00:00:00', $today.' 23:59:59'];
    }

    /**
     * 昨日时间段
     *
     * @return string[]
     */
    public static function rangeYesterday()
    {
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        return [$yesterday.' 00:00:00', $yesterday.' 23:59:59'];
    }

    public static function rangeDays(int $days, $end_day = false)
    {
        $yesterday = date('Y-m-d', strtotime("{$days} day"));
        $today = $end_day ?: date('Y-m-d');
        if ($yesterday > $today) {
            return [$today.' 00:00:00', $yesterday.' 23:59:59'];
        } else {
            return [$yesterday.' 00:00:00', $today.' 23:59:59'];
        }
    }

    public static function rangeWeek()
    {
        $week_num = date('N');

        return self::rangeDays(-($week_num - 1));
    }

    public static function rangeLastWeek()
    {
        $week_num = date('N');

        return self::rangeDays(-($week_num - 1) - 7, date('Y-m-d', strtotime(-$week_num.' days')));
    }

    public static function rangeMonth()
    {
        $month_num = date('j');

        return self::rangeDays(-($month_num - 1));
    }

    public static function rangeLastMonth()
    {
        $month_first_day = date('Y-m-01', strtotime('-1 month'));
        $month_end_day = date('Y-m-t', strtotime('-1 month'));

        return [$month_first_day.' 00:00:00', $month_end_day.' 23:59:59'];
    }
}
