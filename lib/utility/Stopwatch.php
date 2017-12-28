<?php
namespace Freischutz\Utility;

/**
 * Freischutz\Utility\Stopwatch
 */
class Stopwatch
{
    private $timeStart;
    private $timeMarks;
    private $timeStop;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->timeStart = microtime(true);
        $this->timeMarks = array();
    }

    /**
     * Reset start time to now, clear marks and clear stop time.
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
    public function mark()
    {
        $mark =  microtime(true) - $this->timeStart;
        $this->timeMarks[] = $mark;

        return $mark;
    }

    /**
     * Get marks.
     *
     * @return array
     */
    public function getMarks()
    {
        return $this->timeMarks;
    }

    /**
     * Get time elapsed without stopping.
     *
     * @return float
     */
    public function elapsed()
    {
        $end = !empty($this->timeStop) ? $this->timeStop : microtime(true);

        return $end - $this->timeStart;
    }

    /**
     * Stop timekeeping.
     *
     * @return float
     */
    public function stop()
    {
        $this->timeStop = microtime(true);

        return $this->elapsed();
    }
}
