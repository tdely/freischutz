<?php
namespace Freischutz\Utility;

/**
 * Stopwatch utility for timekeeping.
 *
 * @see       https://gitlab.com/tdely/freischutz/ Freischutz on GitLab
 *
 * @author    Tobias Dély (tdely) <cleverhatcamouflage@gmail.com>
 * @copyright 2017-present Tobias Dély
 * @license   https://directory.fsf.org/wiki/License:BSD-3-Clause BSD-3-Clause
 */
class Stopwatch
{
    /** @var float Starting timestamp. */
    private $timeStart;
    /** @var float[]|null Marked timestamps. */
    private $timeMarks;
    /** @var float|null Stopped timestamp. */
    private $timeStop;

    /**
     * Stopwatch constructor.
     */
    public function __construct()
    {
        $this->timeStart = microtime(true);
        $this->timeMarks = array();
    }

    /**
     * Reset start time to now, clear marks and clear stop time.
     *
     * @return void
     */
    public function reset()
    {
        $this->timeStart = microtime(true);
        $this->timeStop = null;
        $this->timeMarks = array();
    }

    /**
     * Mark time elapsed without stopping.
     *
     * @return float
     */
    public function mark():float
    {
        $mark =  microtime(true) - $this->timeStart;
        $this->timeMarks[] = $mark;

        return $mark;
    }

    /**
     * Get marks.
     *
     * @return float[]
     */
    public function getMarks():array
    {
        return $this->timeMarks;
    }

    /**
     * Get time elapsed without stopping.
     *
     * @return float
     */
    public function elapsed():float
    {
        $end = !empty($this->timeStop) ? $this->timeStop : microtime(true);

        return $end - $this->timeStart;
    }

    /**
     * Stop timekeeping.
     *
     * @return float
     */
    public function stop():float
    {
        $this->timeStop = microtime(true);

        return $this->elapsed();
    }
}
