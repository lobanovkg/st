<?php
/**
 * Created by PhpStorm.
 * User: Lobanov Kyryll
 * Date: 01.03.18
 * Time: 20:25
 */

declare(strict_types=1);

namespace Tests\SocialTrackerBundle\Helper;

use PHPUnit\Framework\TestCase;
use SocialTrackerBundle\Helper\SocialHelper;

/**
 * Class SocialHelperTest
 *
 * @group social-tracker
 */
class SocialHelperTest extends TestCase
{
    /**
     * @covers SocialHelper::parseHashTagsFromString()
     */
    public function testParseHashTagsFromString()
    {
        $testMessage = 'Happy #__ birthday #@joke to this #123 Oscar-winning, #BlackPanther #not-valid-hashtag starring, all-around 👑, @lupitanyongo 🎉';
        $hashtags = SocialHelper::parseHashTagsFromString($testMessage);

        self::assertCount(4, $hashtags);
    }
}
