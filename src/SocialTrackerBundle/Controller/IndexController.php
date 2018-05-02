<?php
/**
 * Created by PhpStorm.
 * User: Kyryll Lobanov
 * Date: 14.01.18
 * Time: 19:57
 */

declare(strict_types=1);

namespace SocialTrackerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Class IndexController
 */
class IndexController extends Controller
{
    /**
     * Index page action
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function indexAction()
    {
        return $this->json([]);
    }
}
