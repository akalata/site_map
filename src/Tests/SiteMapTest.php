<?php

/**
 * @file
 * Contains \Drupal\site_map\Tests\SiteMapTest.
 */

namespace Drupal\site_map\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test case class for sitemap tests.
 *
 * @group site_map
 */
class SiteMapTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('site_map', 'node');

  protected function setUp() {
    parent::setUp();

    // Create user.
    $this->user = $this->drupalCreateUser(array(
      'administer site configuration',
      'access site map',
      //'administer menu',
      'administer nodes',
      //'create page content',
    ));
    $this->drupalLogin($this->user);
  }

  /**
   * Tests that a new node with a menu item gets listed at /sitemap.
   */
  public function testNodeAddition() {
    // Configure module to list items of Main menu.
    $edit = array(
      'site_map_show_menus[main-menu]' => '1',
    );
    $this->drupalPost('admin/config/search/sitemap',
                      $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'));

    // Create dummy node.
    $title = $this->randomString();
    $edit = array(
      'title' => $title,
      'menu[enabled]' => '1',
      'menu[link_title]' => $title,
    );
    $this->drupalPost('node/add/page',
                      $edit, t('Save'));

    // Check that dummy node is listed at /sitemap
    $this->drupalGet('sitemap');
    $this->assertText($title);
  }
}
