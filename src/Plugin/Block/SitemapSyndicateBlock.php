<?php

/**
 * @file
 * Contains \Drupal\site_map\Plugin\Block\SitemapSyndicateBlock.
 */

namespace Drupal\site_map\Plugin\Block;

use Drupal\block\Annotation\Block;
use Drupal\block\BlockBase;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a 'Syndicate (site map)' block.
 *
 * @Block(
 *   id = "site_map_syndicate",
 *   admin_label = @Translation("Syndicate (site map)")
 * )
 */
class SitemapSyndicateBlock extends BlockBase {

  /**
   * Overrides \Drupal\block\BlockBase::defaultConfiguration().
   */
  public function defaultConfiguration() {
    return array(
      'sitemap_block_feed_icon' => TRUE,
      'sitemap_block_more_link' => TRUE,
      'cache' => DRUPAL_NO_CACHE,
    );
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockForm().
   */
  public function blockForm($form, &$form_state) {
    $form['sitemap_block_feed_icon'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display feed icon'),
      '#default_value' => $this->configuration['sitemap_block_feed_icon'],
    );
    $form['sitemap_block_more_link'] = array(
      '#type' => 'checkbox',
      '#title' => t("Display 'More' link"),
      '#size' => 60,
      '#default_value' => $this->configuration['sitemap_block_more_link'],
    );
    return $form;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockSubmit().
   */
  public function blockSubmit($form, &$form_state) {
    $this->configuration['sitemap_block_feed_icon'] = $form_state['values']['sitemap_block_feed_icon'];
    $this->configuration['sitemap_block_more_link'] = $form_state['values']['sitemap_block_more_link'];
  }

  /**
   * Implements \Drupal\block\BlockBase::blockBuild().
   */
  public function build() {
    $output = '';
    $config = \Drupal::config('site_map.settings');
    if ($this->configuration['sitemap_block_feed_icon']) {
      $output .= theme('feed_icon', array(
        'url' => $config->get('site_map_rss_front'),
        'title' => t('Syndicate'),
      ));
    }
    if ($this->configuration['sitemap_block_more_link']) {
      $output .= theme('more_link', array(
        'url' => 'sitemap',
        'title' => t('View the site map to see more RSS feeds.'),
      ));
    }

    return array(
      '#type' => 'markup',
      '#markup' => $output,
    );
  }

}
