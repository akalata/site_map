<?php

/**
 * @file
 * Site map theme functions.
 */

use Drupal\Component\Utility\String;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

/**
 * Returns HTML for a site map feed icon legend.
 */
function theme_site_map_rss_legend() {
  $output = '';
  $output .= '<p><strong>' . t('Legend:') . '</strong><br />';
  $feed_icon_node = array(
    '#theme' => 'site_map_feed_icon',
    '#type' => 'node',
  );
  $output .= '<span class="rss">' . drupal_render($feed_icon_node) . '</span> ' . t('Link to a content RSS feed');
  $feed_icon_comment = array(
    '#theme' => 'site_map_feed_icon',
    '#type' => 'comment',
  );
  $output .= '<br /><span class="rss">' . drupal_render($feed_icon_comment) . '</span> ' . t('Link to a comment RSS feed');
  $output .= '</p>';

  return $output;
}

/**
 * Preprocesses the variables for theme_site_map_box().
 */
function template_preprocess_site_map_box(&$variables) {
  $variables['attributes']['class'][] = 'site-map-box';
}

/**
 * Returns HTML for a themed site map box.
 *
 * @param array $variables
 *   An associative array containing:
 *   - title: The subject of the box.
 *   - content: The content of the box.
 *   - attributes:  Optional attributes for the box.
 *   - options:  Options are set by site_map.helper service.
 *
 * @return string
 *   Returns sitemap display in DIV.
 */
function theme_site_map_box(array $variables) {
  $title = $variables['title'];
  $content = $variables['content'];
  $attributes = $variables['attributes'];
  $options = $variables['options'];

  $output = '';
  if (!empty($title) || !empty($content)) {
    $output .= '<div' . new Attribute($attributes) . '>';
    if (!empty($title) && isset($options['show_titles'])) {
      $output .= '<h2 class="title">' . $title . '</h2>';
    }
    if (!empty($content)) {
      $output .= '<div class="content">' . $content . '</div>';
    }
    $output .= '</div>';
  }

  return $output;
}

/**
 * Returns HTML for a feed icon with link.
 *
 * @param array $variables
 *   An associative array containing:
 *   - url: The url of the feed.
 *   - name: The name of the feed.
 *   - type: The type of feed icon.
 *
 * @return string
 *   Constructs and returns html with feed image icon.
 */
function theme_site_map_feed_icon(array $variables) {
  $output = '';

  switch ($variables['type']) {
    case 'node':
      $image = array(
        '#theme' => 'image',
        '#uri' => drupal_get_path('module', 'site_map') . '/images/feed-small.png',
        '#title' => t('Syndicated feed icon'),
        '#alt' => t('Syndicated feed icon'),
      );
      $output = drupal_render($image);
      break;

    case 'comment':
      $image = array(
        '#theme' => 'image',
        '#uri' => drupal_get_path('module', 'site_map') . '/images/feed-small-comment.png',
        '#title' => t('Syndicated feed icon'),
        '#alt' => t('Syndicated feed icon'),
      );
      $output = drupal_render($image);
      break;
  }

  if (!empty($variables['url'])) {
    $output = \Drupal::l($output, Url::fromUri('base://' . $variables['url'], array(
      'attributes' => array(
        'class' => 'feed-link',
        'title' => t('Syndicated feed for') . ' ' . $variables['name'],
      ),
      'html' => TRUE,
    )));
  }

  return $output;
}

/**
 * Preprocesses the rendered tree for theme_site_map_menu_tree().
 *
 * This is a clone of the core template_preprocess_menu_tree() function
 * with the exception of the site_map specific class name used in the
 * UL that also allow themers to override the function only
 * for the site map page.
 */
function template_preprocess_site_map_menu_tree(&$variables) {
  $variables['tree'] = $variables['tree']['#children'];
}

/**
 * Returns HTML for a wrapper for a menu sub-tree.
 *
 * This is a clone of the core theme_menu_tree() function with the exception of
 * the site_map specific class name used in the UL that also allow themers to
 * override the function only for the site map page.
 *
 * @param array $variables
 *   An associative array containing:
 *   - tree: An HTML string containing the tree's items.
 *
 * @return string
 *   Returns the html string with the <ul> for the menu tree.
 *
 * @see template_preprocess_menu_tree()
 * @ingroup themeable
 */
function theme_site_map_menu_tree(array $variables) {
  return '<ul class="site-map-menu">' . $variables['tree'] . '</ul>';
}

/**
 * Returns HTML for a menu link and submenu.
 *
 * This is a one by one clone of the core theme_menu_link() function that allows
 * custom theming of the site map page items.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: Structured array data for a menu link.
 *
 * @return string
 *   Returns html string for menu link.
 *
 * @ingroup themeable
 */
function theme_site_map_menu_link(array $variables) {
  $element = $variables['element'];
  $sub_menu = '';

  if ($element['#below']) {
    $sub_menu = drupal_render($element['#below']);
  }
  /** @var \Drupal\Core\Url $url */
  $url = $element['#url'];
  $url->setOption('set_active_class', TRUE);
  $output = \Drupal::linkGenerator()->generate($element['#title'], $url);
  return '<li' . new Attribute($element['#attributes']) . '>' . $output . $sub_menu . "</li>\n";
}


/**
 * Preprocesses the variables for site-map.tpl.php.
 *
 * @see site-map.tpl.php
 */
function template_preprocess_site_map(&$variables) {
  $config = \Drupal::config('site_map.settings');

  $message = $config->get('site_map_message.value');
  if (!empty($message)) {
    $variables['message'] = String::checkPlain($message, $config->get('site_map_message.format'));
  }

  if (($config->get('site_map_show_rss_links') != 0) && \Drupal::service('module_handler')->moduleExists('commentrss') && \Drupal::config('commentrss.settings')->get('commentrss_site')) {
    $rss_legend = array(
      '#theme' => 'site_map_rss_legend',
    );
    $variables['rss_legend'] = drupal_render($rss_legend);
  }

  if ($config->get('site_map_show_titles')) {
    $variables['show_titles'] = TRUE;
  }

  $variables['site_map'] = '';
  $site_map_order = $config->get('site_map_order');
  asort($site_map_order);

  foreach ($site_map_order as $content => $weight) {
    // Get type of content.
    $type = substr($content, 0, strpos($content, '_'));
    $id = substr($content, strpos($content, '_') + 1);
    if (empty($type)) {
      $type = $content;
      $id = NULL;
    }

    switch ($type) {
      case 'front':
        if ($config->get('site_map_show_front')) {
          $variables['site_map'] .= _site_map_front_page();
        }
        break;

      case 'blogs':
        if ($config->get('site_map_show_blogs')) {
          $variables['site_map'] .= _site_map_blogs();
        }
        break;

      case 'books':
        $books = $config->get('site_map_show_books');
        if (!empty($books)) {
          $variables['site_map'] .= _site_map_books();
        }
        break;

      case 'menus':
        $menus = array_filter($config->get('site_map_show_menus'));
        if (!empty($menus[$id])) {
          $variables['site_map'] .= _site_map_menus($id);
        }
        break;

      case 'faq':
        if ($config->get('site_map_show_faq')) {
          $variables['site_map'] .= _site_map_faq();
        }
        break;

      case 'vocabularies':
        $vocabulary = entity_load('taxonomy_vocabulary', $id);
        $vocabularies = $config->get('site_map_show_vocabularies');

        if (!empty($vocabularies[$vocabulary->id()])) {
          // Compile the vocabulary trees.
          $variables['site_map'] .= \Drupal::service('site_map.helper')->getTaxonomys($vocabulary);
        }
        break;
    }
  }

  // Invoke all custom modules and integrate themed HTML into the site map.
  $additional = \Drupal::service('module_handler')->invokeAll('site_map');
  foreach ($additional as $themed_site_map_code) {
    $variables['additional'] .= $themed_site_map_code;
  }
}

/**
 * Returns HTML for the site map order form.
 *
 * Copied from the core theme_filter_admin_format_filter_order() function.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: A render element representing the form.
 *
 * @ingroup themeable
 */
function theme_site_map_order(array $variables) {
  $element = $variables['element'];

  // Site map order (tabledrag).
  $rows = array();
  foreach (Element::children($element, TRUE) as $name) {
    $element[$name]['weight']['#attributes']['class'][] = 'site-map-order-weight';
    $rows[] = array(
      'data' => array(
        drupal_render($element[$name]['content']),
        drupal_render($element[$name]['weight']),
      ),
      'class' => array('draggable'),
    );
  }
  $output = drupal_render_children($element);
  $table = array(
    '#type' => 'table',
    '#rows' => $rows,
    '#attributes' => array('id' => 'site-map-order'),
    '#tabledrag' => array(
      array(
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'site-map-order-weight',
        'hidden' => TRUE,
      ),
    ),
  );
  $output .= drupal_render($table);

  return $output;
}
