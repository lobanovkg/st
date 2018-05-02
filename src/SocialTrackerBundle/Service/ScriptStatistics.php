<?php
/**
 * Created by PhpStorm.
 * User: Lobanov Kyryll
 * Date: 16.02.18
 * Time: 22:46
 */

declare(strict_types=1);

namespace SocialTrackerBundle\Service;

use Monolog\Logger;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ScriptStatistics - helper which help track time statistic
 */
class ScriptStatistics
{
    /** @var float Time statistic */
    private static $time;

    /**
     * Start statistic
     */
    public static function start()
    {
        /** Start time tracker */
        self::$time = -microtime(true);
    }

    /**
     * Write statistic data to outputs
     *
     * @param Logger|null          $logger Monolog logger
     * @param null|OutputInterface $output Symfony console output
     */
    public static function end(?Logger $logger = null, ?OutputInterface $output = null)
    {
        /** End time tracker */
        self::$time += microtime(true);

        /** Script statistic */
        $executionTime = 'Execution time: '.round(self::$time, 2).' sec.';
        $memoryLimit   = 'Memory peak usage: '.(memory_get_peak_usage(true) / 1024 / 1024).' Mb.';

        if (null !== $logger) {
            /** Writing statistic to log file */
            $logger->info($executionTime.' '.$memoryLimit);
        }

        if (null !== $output) {
            /** Show statistic to console */
            $output->writeln($executionTime);
            $output->writeln($memoryLimit);
        }
    }
}
