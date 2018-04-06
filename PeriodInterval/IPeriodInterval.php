<?php

namespace Arrynn\LaravelHelpers\PeriodInterval;

use Illuminate\Support\Carbon;

interface IPeriodInterval
{
    /**
     * Creates instance of the period interval class
     *
     * @param int $period
     * @param Carbon $now custom date instead of actual
     * @return IPeriodInterval
     */
    public static function get(int $period = 6, Carbon $now = null): IPeriodInterval;

    /**
     * Returns the start date of the period
     *
     * @return Carbon
     */
    public function getStart(): Carbon;


    /**
     * Returns the end date of the period
     *
     * @return Carbon
     */
    public function getEnd(): Carbon;


    /**
     * Returns the inner intervals of the period
     *
     * Inner intervals are instances of IPeriodInterval with a narrower start and end date
     * distinguished by the concrete implementation
     *
     * @return IPeriodInterval[]
     */
    public function getIntervals(): array;

    /**
     * Outputs the dates in a string format.
     *
     * @return string
     */
    public function toString(): string;
}