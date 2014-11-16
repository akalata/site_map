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
  public static $modules = array('site_map', 'node', 'menu_ui');

  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));

    // Create user.
    $this->user = $this->drupalCreateUser(array(
      'administer site configuration',
      'access site map',
      'access administration pages',
      'administer menu',
      'administer nodes',
      'create page content',
    ));
    $this->drupalLogin($this->user);
  }

  /**
   * Tests that a new node with a menu item gets listed at /sitemap.
   */
  public function testNodeAddition() {
    // Configure module to list items of Main menu.
    $edit = array(
      'site_map_show_menus[main]' => '1',
    );
    $this->drupalPostForm('admin/config/search/sitemap',
                      $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'));

    // Create dummy node.
    $title = $this->randomString();
    $edit = array(
      'title[0][value]' => $title,
      'menu[enabled]' => '1',
      'menu[title]' => $title,
    );
    $this->drupalPostForm('node/add/page',
                      $edit, t('Save and publish'));

    // Check that dummy node is listed at /sitemap
    $this->drupalGet('sitemap');
    $this->assertLink($title);
  }
}
