<?php

namespace Arrynn\LaravelHelpers\PeriodInterval;

use Exception;
use Illuminate\Support\Carbon;

/**
 * Class MonthlyPeriodInterval
 */
class MonthlyPeriodInterval implements IPeriodInterval
{
    const PRECISION_MONTH = 'month';
    const PRECISION_DAY = 'day';

    /**
     * @var int the day/month precision programmatic difference
     */
    private static $day_month_precision_diff = 1;

    /**
     * @var array the allowed precision types
     */
    private static $precisions = [
        self::PRECISION_MONTH,
        self::PRECISION_DAY
    ];

    /**
     * @var Carbon $now the date from which the period is determined
     */
    private $now;
    /**
     * @var string $precision the precision
     */
    private $precision;
    /**
     * @var int $period number of months in the generated period
     */
    private $period;
    /**
     * @var Carbon $start Start of the interval
     */
    private $start;
    /**
     * @var Carbon $end End of the interval
     */
    private $end;
    /**
     * Period's inner intervals that are by default distinguished by month.
     *
     * @var IPeriodInterval[] $intervals
     */
    private $intervals;

    /**
     * @var bool shows whether the period has inner intervals
     */
    private $hasInnerIntervals;

    private function __construct(int $period, string $precision, Carbon $now = null)
    {
        if ($now === null) {
            $now = Carbon::now(config('app.output_timezone'));
        }
        $this->now = $now;
        // the hour is set to 12 to absorb the margin
        // of daylight saving times
        $this->now->hour(12);
        $this->now->minute(0);
        $this->now->second(0);
        $this->precision = $precision;
        $this->period = $period;
        $this->init();
    }

    /**
     * {@inheritdoc}
     */
    public static function get(int $period = 6, Carbon $now = null): IPeriodInterval
    {
        return new self($period, self::PRECISION_MONTH, $now);
    }

    /**
     * {@inheritdoc}
     */
    public function getStart(): Carbon
    {
        return $this->start;
    }

    /**
     * {@inheritdoc}
     */
    public function getEnd(): Carbon
    {
        return $this->end;
    }

    /**
     * {@inheritdoc}
     */
    public function getIntervals(): array
    {
        return $this->intervals;
    }

    /**
     * {@inheritdoc}
     */
    public function toString(): string
    {
        $period = $this->period;
        $now = $this->now;
        $start = $this->start->toDateTimeString();
        $end = $this->end->toDateTimeString();

        $str = "Date Period\n";
        $str .= "-----------\n";

        $str .= "Months:\t$period\n";
        $str .= "Now:\t$now\n";
        $str .= "Start:\t$start\n";
        $str .= "End:\t$end\n";

        if ($this->hasInnerIntervals) {
            $str .= "\nInner intervals:\n";
            foreach ($this->intervals as $interval) {
                $str .= "\n";
                $str .= $interval->toString();
            }
        }
        return $str;
    }

    /**
     * Reconfigures the period with new precision type.
     * @see Period constants
     *
     * @param string $precision
     * @throws Exception
     */
    public function setPrecision(string $precision): void
    {
        if (!in_array($precision, self::$precisions)) {
            throw new Exception("Invalid precision type");
        }

        $this->precision = $precision;
        $this->init();
    }


    /**
     * Initialises start and end date and creates
     * inner intervals if the period is longer than 1 month
     */
    private function init()
    {
        $this->setStart();
        // daylight saving times reset
        $this->start->hour(0);
        $this->setEnd();
        if ($this->period > 1) {
            $this->setIntervals();
            $this->hasInnerIntervals = true;
        } else {
            $this->intervals = [];
            $this->hasInnerIntervals = false;
        }
    }


    /**
     * Sets the start date
     *
     * @return void
     */
    private function setStart(): void
    {
        $date = $this->now->copy();
        if ($this->precision === self::PRECISION_MONTH) {
            $start = $date->subMonths($this->period - self::$day_month_precision_diff);
            $this->start = $start->day(1);
        } else {
            $start = $date->subMonths($this->period);
            $this->start = $start;
            $this->start = $start->addDay();
        }
    }

    /**
     * Sets the end date
     *
     * @return void
     */
    private function setEnd(): void
    {
        $date = $this->now->copy();
        $date->hour(23);
        $date->minute(59);
        $date->second(59);
        $end = $date;
        if ($this->precision === self::PRECISION_MONTH) {
            $this->end = $end->day($end->daysInMonth);
        } else {
            $this->end = $end;
        }
    }


    /**
     * Sets the monthly intervals for periods longer than 1 month.
     */
    private function setIntervals()
    {
        $this->intervals = [];
        for ($month = 0; $month < $this->period; $month++) {
            if ($this->precision === self::PRECISION_DAY) {

                // the hour is set to 12 to absorb the margin
                // of daylight saving times
                $start = $this->start->copy()->hour(12)
                    ->addMonth($month + self::$day_month_precision_diff);

                // Day precision parent is adding a day to start
                // of the period. E.G.: 2018-01-15 00:00:00:00 => 2018-02-14 23:59:59
                // This needs to be substracted for inner intervals
                $start->subDay();
            } else {
                // the hour is set to 12 to absorb the margin
                // of daylight saving times
                $start = $this->start->copy()->hour(12)
                    ->addMonth($month);
            }
            $interval = Period::get(1, $this->precision, $start);
            $this->intervals[] = $interval;
        }
    }
}