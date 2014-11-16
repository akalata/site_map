<?php

/**
 * @file
 * Contains \Drupal\site_map\Plugin\Block\SitemapSyndicateBlock.
 */

namespace Drupal\site_map\Plugin\Block;

use Drupal\block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

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
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'sitemap_block_feed_icon' => TRUE,
      'sitemap_block_more_link' => TRUE,
      'cache' => array(
        // No caching.
        'max_age' => 0,
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
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
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['sitemap_block_feed_icon'] = $form_state->getValue('sitemap_block_feed_icon');
    $this->configuration['sitemap_block_more_link'] = $form_state->getValue('sitemap_block_more_link');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $output = '';
    $config = \Drupal::config('site_map.settings');
    if ($this->configuration['sitemap_block_feed_icon']) {
      $feed_icon = array(
        '#theme' => 'feed_icon',
        '#url' => $config->get('site_map_rss_front'),
        '#title' => t('Syndicate'),
      );
      $output .= drupal_render($feed_icon);
    }
    if ($this->configuration['sitemap_block_more_link']) {
      $more_link = array(
        '#type' => 'more_link',
        '#url' => Url::fromUri('base://sitemap'),
        '#attributes' => array('title' => t('View the site map to see more RSS feeds.')),
      );
      $output .= drupal_render($more_link);
    }

    return array(
      '#type' => 'markup',
      '#markup' => $output,
    );
  }

}
