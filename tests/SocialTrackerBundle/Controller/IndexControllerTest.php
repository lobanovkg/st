<?php
/**
 * Created by PhpStorm.
 * User: Kyryll Lobanov
 * Date: 23.02.18
 * Time: 15:57
 */

declare(strict_types=1);

namespace Tests\SocialTrackerBundle\Controller;

use SocialTrackerBundle\Controller\IndexController;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class IndexControllerTest
 *
 * @group social-tracker
 */
class IndexControllerTest extends WebTestCase
{
    /**
     * Test index response controller action
     *
     * @covers IndexController::indexAction()
     */
    public function testIndexAction()
    {
        $client = static::createClient();

        $client->request('GET', '/');

        self::assertContains('[]', $client->getResponse()->getContent());
    }
}
