<?php
/**
 * Created by PhpStorm.
 * User: Lobanov Kyryll
 * Date: 28.02.18
 * Time: 15:12
 */

declare(strict_types=1);

namespace Tests\SocialTrackerBundle;

use PHPUnit\DbUnit\DataSet\YamlDataSet;

/**
 * Class AbstractDataSet
 */
abstract class AbstractDataSet
{
    /**
     * @return YamlDataSet
     */
    abstract public static function getDataSet(): YamlDataSet;

    /**
     * Get yaml dataSet by file name
     *
     * @param string $fileName Data set file name
     *
     * @return YamlDataSet
     */
    public static function getDataSetByYamlFileName(string $fileName)
    {
        $filePath = __DIR__."/Helper/_files/{$fileName}";

        return new YamlDataSet($filePath);
    }
}
