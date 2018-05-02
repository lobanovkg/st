<?php
/**
 * Created by PhpStorm.
 * User: Lobanov Kyryll
 * Date: 28.02.18
 * Time: 18:44
 */

declare(strict_types=1);

namespace Tests\SocialTrackerBundle\Recording\Event\Validator;

use PHPUnit\Framework\TestCase;
use SocialTrackerBundle\Recording\Event\EventData;
use SocialTrackerBundle\Recording\Event\Validator\Validate;
use Tests\SocialTrackerBundle\Recording\Event\EventDataDataProviderTrait;

/**
 * Class ValidateTest
 *
 * @group social-tracker
 */
class ValidateTest extends TestCase
{
    /** Data provider trait */
    use EventDataDataProviderTrait;

    /** @var Validate */
    private static $validator;

    /**
     * @inheritdoc
     */
    public static function setUpBeforeClass()
    {
        self::$validator = new Validate();
    }

    /**
     * Testing validate
     *
     * @param EventData $eventData Event data
     *
     * @dataProvider getNotValidEventData
     *
     * @covers       Validate::validate()
     */
    public function testNotValidEventData(EventData $eventData)
    {
        self::$validator->setData($eventData);
        self::assertEquals(Validate::ERROR_MESSAGE_EMPTY_ARRAY, self::$validator->validate());
    }

    /**
     * Testing validate
     *
     * @param EventData $eventData Event data
     *
     * @dataProvider getValidEventData
     *
     * @covers       Validate::validate()
     */
    public function testValidEventData(EventData $eventData)
    {
        self::$validator->setData($eventData);
        self::assertTrue(self::$validator->validate());
    }
}
