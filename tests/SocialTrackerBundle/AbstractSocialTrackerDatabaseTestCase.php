<?php
/**
 * Created by PhpStorm.
 * User: Lobanov Kyryll
 * Date: 27.02.18
 * Time: 19:43
 */

declare(strict_types=1);

namespace Tests\SocialTrackerBundle;

use PHPUnit\DbUnit\TestCaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\Container;

/**
 * Class SocialTrackerDatabaseTestCase
 */
abstract class AbstractSocialTrackerDatabaseTestCase extends WebTestCase
{
    use TestCaseTrait;

    /** Only instantiate pdo once for test clean-up/fixture load */
    static private $pdo = null;

    /** Only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test */
    private $conn = null;

    /** @var Container Symfony DI */
    protected static $container;

    /**
     * Set up once before all tests
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $kernel          = self::bootKernel();
        self::$container = $kernel->getContainer();
    }

    /**
     * Get test db connection
     *
     * @return null|\PHPUnit\DbUnit\Database\DefaultConnection
     */
    final public function getConnection()
    {
        if ($this->conn === null) {
            if (null === self::$pdo) {
                self::$pdo = new \PDO($GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD']);
            }
            $this->conn = $this->createDefaultDBConnection(self::$pdo);
        }

        return $this->conn;
    }
}
