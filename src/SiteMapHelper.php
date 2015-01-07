<?php

/**
 * @file
 * Contains \Drupal\site_map\SitemapHelper.
 */

namespace Drupal\site_map;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Url;

/**
 * Defines a helper class for stuff related to views data.
 */
class SiteMapHelper {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a SitemapHelper object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Sets options based on admin input paramaters for redering.
   *
   * @param array $options
   *   The array of options to the site map theme.
   * @param string $option_string
   *   The string index given from the admin form to match.
   * @param int $equal_param
   *   Result of param test, 0 or 1.
   * @param string $set_string
   *   Index of option to set, or the option name.
   * @param bool $set_value
   *   The option, on or off, or strings or ints for other options.
   */
  public function setOption(array &$options, $option_string, $equal_param, $set_string, $set_value) {
    $config = $this->configFactory->get('site_map.settings');
    if ($config->get($option_string) == $equal_param) {
      $options[$set_string] = $set_value;
    }
  }

  /**
   * Render the latest maps for the taxonomy tree.
   *
   * @return string
   *   Returns HTML string of site map for taxonomies.
   */
  public function getTaxonomys() {
    $output = '';
    $config = $this->configFactory->get('site_map.settings');
    $vids = array_filter($config->get('site_map_show_vocabularies'));
    if (!empty($vids)) {
      $vocabularies = entity_load_multiple('taxonomy_vocabulary', $vids);
      foreach ($vocabularies as $vocabulary) {
        $output .= $this->getTaxonomyTree($vocabulary->vid, $vocabulary->name, $vocabulary->description);
      }
    }

    return $output;
  }

  /**
   * Render the taxonomy tree.
   *
   * @param string $vid
   *   Vocabulary id.
   * @param string $name
   *   An optional name for the tree. (Default: NULL).
   * @param string $description
   *   $description An optional description of the tree. (Default: NULL).
   *
   * @return string
   *   A string representing a rendered tree.
   */
  public function getTaxonomyTree($vid, $name = NULL, $description = NULL) {
    $output = '';
    $options = array();
    $class = array();

    $title = $name ? String::checkPlain($name) : '';

    $config = \Drupal::config('site_map.settings');
    $threshold = $config->get('site_map_term_threshold');

    // Taxonomy terms depth.
    $depth = $config->get('site_map_categories_depth');
    if ($depth <= -1) {
      $depth = NULL;
    }

    // RSS depth.
    $rss_depth = $config->get('site_map_rss_depth');

    $forum_link = FALSE;

    $last_depth = -1;

    $output .= !empty($description) && $config->get('site_map_show_description') ? '<div class="description">' . Xss::filterAdmin($description) . "</div>\n" : '';

    // taxonomy_get_tree() honors access controls.
    // Included patch from https://www.drupal.org/node/1593556.
    $tree = taxonomy_get_tree($vid, 0, $depth);
    foreach ($tree as $term) {
      $term->count = count(taxonomy_select_nodes($term->tid));
      if ($term->count <= $threshold) {
        continue;
      }

      // Adjust the depth of the <ul> based on the change
      // in $term->depth since the $last_depth.
      if ($term->depth > $last_depth) {
        for ($i = 0; $i < ($term->depth - $last_depth); $i++) {
          $output .= "\n<ul>";
        }
      }
      elseif ($term->depth == $last_depth) {
        $output .= '</li>';
      }
      elseif ($term->depth < $last_depth) {
        for ($i = 0; $i < ($last_depth - $term->depth); $i++) {
          $output .= "</li>\n</ul>\n</li>";
        }
      }
      // Display the $term.
      $output .= "\n<li>";
      $term_item = '';
      if ($forum_link) {
        $term_item .= \Drupal::l($term->name, Url::fromRoute('forum.page', array('taxonomy_term' => $term->tid), array('attributes' => array('title' => $term->description__value))));
      }
      elseif ($term->count) {
        $term_item .= \Drupal::l($term->name, Url::fromRoute('entity.taxonomy_term.canonical', array('taxonomy_term' => $term->tid), array('attributes' => array('title' => $term->description__value))));
      }
      else {
        $term_item .= String::checkPlain($term->name);
      }
      if ($config->get('site_map_show_count')) {
        $term_item .= " ($term->count)";
      }

      if ($config->get('site_map_show_rss_links') != 0 && ($rss_depth == -1 || $term->depth < $rss_depth)) {
        $feed_icon = array(
          '#theme' => 'site_map_feed_icon',
          '#url' => 'taxonomy/term/' . $term->tid . '/feed',
        );
        $rss_link = drupal_render($feed_icon);
        if ($config->get('site_map_show_rss_links') == 1) {
          $term_item .= ' ' . $rss_link;
        }
        else {
          $class[] = 'site-map-rss-left';
          $term_item = $rss_link . ' ' . $term_item;
        }
      }

      $output .= $term_item;

      // Reset $last_depth in preparation for the next $term.
      $last_depth = $term->depth;
    }

    // Bring the depth back to where it began, -1.
    if ($last_depth > -1) {
      for ($i = 0; $i < ($last_depth + 1); $i++) {
        $output .= "</li>\n</ul>\n";
      }
    }
    \Drupal::service('site_map.helper')->setOption($options, 'site_map_show_titles', 1, 'show_titles', TRUE);

    $class[] = 'site-map-box-terms';
    $class[] = 'site-map-box-terms-' . $vid;
    $attributes = array('class' => $class);

    $site_map_box = array(
      '#theme' => 'site_map_box',
      '#title' => $title,
      '#content' => $output,
      '#attributes' => $attributes,
      '#options' => $options,
    );

    return drupal_render($site_map_box);
  }

}
