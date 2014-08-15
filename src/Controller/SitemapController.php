<?php

/**
 * @file
 * Contains \Drupal\site_map\Controller\SitemapController.
 */

namespace Drupal\site_map\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for update routes.
 */
class SitemapController implements ContainerInjectionInterface {

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs update status data.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module Handler Service.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler')
    );
  }

  public function buildPage() {
    $site_map = array(
      '#theme' => 'site_map',
    );

    $config = \Drupal::config('site_map.settings');
    if ($config->get('site_map_css') != 1) {
      $site_map['#attached']['css'][drupal_get_path('module', 'site_map') . '/site_map.theme.css'] = array();
    }

    return $site_map;
//    return drupal_render($site_map);
  }

  /**
   * Returns site map page's title.
   *
   * @return string
   */
  public function getTitle() {
    $config = \Drupal::config('site_map.settings');
    return $config->get('site_map_page_title');
  }

}
