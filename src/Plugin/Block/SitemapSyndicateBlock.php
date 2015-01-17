<?php

/**
 * @file
 * Contains \Drupal\site_map\Plugin\Block\SitemapSyndicateBlock.
 */

namespace Drupal\site_map\Plugin\Block;

use Drupal\block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Provides a 'Syndicate (site map)' block.
 *
 * @Block(
 *   id = "site_map_syndicate",
 *   label = @Translation("Syndicate"),
 *   admin_label = @Translation("Syndicate (site map)")
 * )
 */
class SitemapSyndicateBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'cache' => array(
        // No caching.
        'max_age' => 0,
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return ($account->hasPermission('access content'));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = \Drupal::config('site_map.settings');
    $route_name = \Drupal::routeMatch()->getRouteName();

    if ($route_name == 'blog.user_rss') {
      $feedurl = Url::fromRoute('blog.user_rss', array(
        'user' => \Drupal::routeMatch()->getParameter('user'),
      ));
    }
    elseif ($route_name == 'blog.blog_rss') {
      $feedurl = Url::fromRoute('blog.blog_rss');
    }
    else {
      $feedurl = $config->get('site_map_rss_front');
    }

    $feed_icon = array(
      '#theme' => 'feed_icon',
      '#url' => $feedurl,
      '#title' => t('Syndicate'),
    );
    $output = drupal_render($feed_icon);
    // Re-use drupal core's render element.
    $more_link = array(
      '#type' => 'more_link',
      '#url' => Url::fromRoute('site_map.page'),
      '#attributes' => array('title' => t('View the site map to see more RSS feeds.')),
    );
    $output .= drupal_render($more_link);

    return array(
      '#type' => 'markup',
      '#markup' => $output,
    );
  }

}
