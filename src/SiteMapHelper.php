<?php

/**
 * @file
 * Contains \Drupal\site_map\SitemapHelper.
 */

namespace Drupal\site_map;

use Drupal\Core\Config\ConfigFactoryInterface;

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
  public function setOption(&$options, $option_string, $equal_param, $set_string, $set_value) {
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

}

