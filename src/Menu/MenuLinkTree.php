<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\MenuLinkTree.
 */

namespace Drupal\site_map\Menu;

use Drupal\Core\Menu\MenuLinkTree as CoreMenuLinkTree;
use Drupal\Component\Utility\NestedArray;

/**
 * Implements the loading, transforming and rendering of menu link trees.
 */
class MenuLinkTree extends CoreMenuLinkTree {

  /**
   * Returns a rendered menu tree.
   *
   * This is a clone of the core Drupal\Core\Menu\MenuLinkTree::build() function
   * with the exception of theme('site_map_menu_tree') for theming override
   * reasons.
   */
  public function buildForSiteMap(array $tree) {
    $config = \Drupal::config('site_map.settings');
    $build = array();
    $items = array();

    // Pull out just the menu links we are going to render so that we
    // get an accurate count for the first/last classes.
    // Thanks for fix by zhuber at https://drupal.org/node/1331104#comment-5200266
    foreach ($tree as $data) {
      /** @var \Drupal\Core\Menu\MenuLinkInterface $link */
      $link = $data->link;
      if (!$link->isHidden() || $config->get('site_map_show_menus_hidden')) {
        $items[] = $data;
      }
    }

    $num_items = count($items);
    foreach ($items as $i => $data) {
      $class = array();
      if ($i == 0) {
        $class[] = 'first';
      }
      if ($i == $num_items - 1) {
        $class[] = 'last';
      }
      /** @var \Drupal\Core\Menu\MenuLinkInterface $link */
      $link = $data->link;
      // Set a class for the <li>-tag. Only set 'expanded' class if the link
      // also has visible children within the current tree.
      if ($data->hasChildren && !empty($data->subtree)) {
        $class[] = 'expanded';
      }
      elseif ($data->hasChildren) {
        $class[] = 'collapsed';
      }
      else {
        $class[] = 'leaf';
      }
      // Set a class if the link is in the active trail.
      if ($data->inActiveTrail) {
        $class[] = 'active-trail';
      }

      // Allow menu-specific theme overrides.
      $element['#theme'] = 'site_map_menu_link__' . strtr($link->getMenuName(), '-', '_');
      $element['#attributes']['class'] = $class;
      $element['#title'] = $link->getTitle();
      $element['#url'] = $link->getUrlObject();
      $element['#below'] = $data->subtree ? $this->buildForSiteMap($data->subtree) : array();
      if (isset($data->options)) {
        $element['#url']->setOptions(NestedArray::mergeDeep($element['#url']->getOptions(), $data->options));
      }
      $element['#original_link'] = $link;
      // Index using the link's unique ID.
      $build[$link->getPluginId()] = $element;
    }
    if ($build) {
      // Make sure drupal_render() does not re-order the links.
      $build['#sorted'] = TRUE;
      // Get the menu name from the last link.
      $menu_name = $link->getMenuName();
      // Add the theme wrapper for outer markup.
      // Allow menu-specific theme overrides.
      $build['#theme_wrappers'][] = 'site_map_menu_tree__' . strtr($menu_name, '-', '_');
      // Set cache tag.
      $build['#cache']['tags']['menu'][$menu_name] = $menu_name;
    }

    return $build;
  }

}