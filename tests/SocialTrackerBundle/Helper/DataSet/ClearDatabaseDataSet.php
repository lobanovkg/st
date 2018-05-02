<?php
/**
 * Created by PhpStorm.
 * User: Lobanov Kyryll
 * Date: 01.03.18
 * Time: 17:10
 */

declare(strict_types=1);

namespace Tests\SocialTrackerBundle\Helper\DataSet;

use PHPUnit\DbUnit\DataSet\YamlDataSet;
use Tests\SocialTrackerBundle\AbstractDataSet;

/**
 * Class ClearDatabaseDataSet
 */
class ClearDatabaseDataSet extends AbstractDataSet
{
    /**
     * Get data set
     *
     * @return \PHPUnit\DbUnit\DataSet\YamlDataSet
     */
    public static function getDataSet(): YamlDataSet
    {
        return self::getDataSetByYamlFileName('clear-database.yml');
    }
}
