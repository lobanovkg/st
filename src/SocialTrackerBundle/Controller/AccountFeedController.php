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
use SocialTrackerBundle\Repository\SocialType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller handles searching posts by live events.
 */
class AccountFeedController extends Controller
{
    /**
     * Searching posts by live events, filtering by account id and hashtags
     *
     * @param Request $request       HTTP request
     * @param int     $originEventId Origin event id
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function feedAction(Request $request, int $originEventId)
    {
        $accounts[SocialType::SOCIAL_TYPE_NAME_INSTAGRAM] = $this->validateAccountData($request->get('in', ''));
        $accounts[SocialType::SOCIAL_TYPE_NAME_TWITTER]   = $this->validateAccountData($request->get('tw', ''));
        $accounts[SocialType::SOCIAL_TAG_NAME]            = $this->validateAccountData($request->get('tag', ''));
        $postLimit                                        = (int) $request->get('limit', 50);

        $posts = $this->container->get('social_tracker.feed.service')
            ->getPostsByOriginEventIdAndAccounts($originEventId, $accounts, 'slave', $postLimit);

        /**
         * Format publish date to timestamp
         * Decode instagram json images to array
         * Unset images value for all social type, exclude Instagram
         *
         * @var int $index  Array index
         * @var array $post Social post data
         */
        foreach ($posts as $index => &$post) {
            $post['timestamp'] = strtotime($post['timestamp']);
            if (SocialType::SOCIAL_TYPE_NAME_INSTAGRAM === $post['social']) {
                $post['images'] = json_decode($post['images'], true);
                continue;
            }
            unset($posts[$index]['images']);
        }
        unset($post);

        return $this->json(['data' => $posts]);
    }

    /**
     * Validate POST data format
     *
     * @param string $data POST variable
     *
     * @return array
     */
    private function validateAccountData(string $data): array
    {
        if (strlen($data)) {
            return explode(',', $data);
        }

        return [];
    }
}
