<?php
/**
 * Created by PhpStorm.
 * User: Lobanov Kyryll
 * Date: 23.02.18
 * Time: 22:21
 */

declare(strict_types=1);

namespace Tests\SocialTrackerBundle\Controller;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Client;

/**
 * Class AccountFeedControllerTest
 *
 * @group social-tracker
 */
class AccountFeedControllerTest extends WebTestCase
{
    /** @var Client Http client */
    private $client;

    /**
     * Setup test parameters
     */
    protected function setUp()
    {
        $this->client = static::createClient();
    }

    /**
     * Testing controller action
     *
     * @covers AccountFeedController::feedAction()
     */
    public function testFeedAction()
    {
        $this->client->request('GET', '/feed/1/');
        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $content = $this->client->getResponse()->getContent();
        self::assertJson($content);

        $this->client->request('POST', '/feed/1/');
        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $content = $this->client->getResponse()->getContent();
        self::assertJson($content);
    }
}
