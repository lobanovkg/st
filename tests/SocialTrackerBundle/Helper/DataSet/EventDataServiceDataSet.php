<?php
/**
 * Created by PhpStorm.
 * User: Lobanov Kyryll
 * Date: 28.02.18
 * Time: 23:42
 */

declare(strict_types=1);

namespace Tests\SocialTrackerBundle\Helper\DataSet;

use PHPUnit\DbUnit\DataSet\YamlDataSet;
use Tests\SocialTrackerBundle\AbstractDataSet;

/**
 * Class EventDataServiceTest
 */
class EventDataServiceDataSet extends AbstractDataSet
{
    /**
     * Get data set
     *
     * @return \PHPUnit\DbUnit\DataSet\YamlDataSet
     */
    public static function getDataSet(): YamlDataSet
    {
        return self::getDataSetByYamlFileName('event-data-service.yml');
    }
}
