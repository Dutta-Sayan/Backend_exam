<?php

namespace Drupal\Tests\file_download_link\Kernel;

/**
 * Class for testing file_download_link_media formatter.
 *
 * @group file_download_link
 * @requires module token
 */
class FileDownloadLinkMediaTokenTest extends FileDownloadLinkMediaTestBase {

  /**
   * The modules to load to run the test.
   *
   * @var array
   */
  protected static $modules = [
    'token',
  ];

  /**
   * {@inheritdoc}
   */
  public static function providerTestFileDownloadLinkMedia() {
    return [
      'no tokens, default settings' => [
        'settings' => [],
        'output' => [
          'title' => 'Download',
          'options' => [
            'attributes' => [
              'class' => [
                'file-download',
                'file-download-text',
                'file-download-plain',
              ],
              'target' => '_blank',
              'download' => TRUE,
            ],
          ],
          'cache' => [
            'tags' => ['file:1'],
            'contexts' => [],
            'max-age' => -1,
          ],
        ],
      ],
      'link text tokens (media and file)' => [
        'settings' => [
          'link_text' => '[media:name] ([file:extension])',
          'new_tab' => FALSE,
          'force_download' => FALSE,
        ],
        'output' => [
          'title' => 'Test Media (txt)',
          'options' => [
            'attributes' => [
              'class' => [
                'file-download',
                'file-download-text',
                'file-download-plain',
              ],
            ],
          ],
          'cache' => [
            'tags' => ['file:1', 'media:1'],
            'contexts' => [],
            'max-age' => -1,
          ],
        ],
      ],
      'tokens in classes' => [
        'settings' => [
          'link_text' => 'Download Media Now!',
          'new_tab' => FALSE,
          'force_download' => FALSE,
          'custom_classes' => 'static-class media-type-[media:bundle:target_id]',
        ],
        'output' => [
          'title' => 'Download Media Now!',
          'options' => [
            'attributes' => [
              'class' => [
                'file-download',
                'file-download-text',
                'file-download-plain',
                'static-class',
                'media-type-test-media',
              ],
            ],
          ],
          'cache' => [
            'tags' => ['file:1', 'media:1'],
            'contexts' => [],
            'max-age' => -1,
          ],
        ],
      ],
      'tokens in title' => [
        'settings' => [
          'link_text' => 'Download Media Now!',
          'link_title' => '[media:bundle:target_id]',
          'new_tab' => FALSE,
          'force_download' => FALSE,
        ],
        'output' => [
          'title' => 'Download Media Now!',
          'options' => [
            'attributes' => [
              'class' => [
                'file-download',
                'file-download-text',
                'file-download-plain',
              ],
              'title' => 'test_media',
            ],
          ],
          'cache' => [
            'tags' => ['file:1', 'media:1'],
            'contexts' => [],
            'max-age' => -1,
          ],
        ],
      ],
    ];
  }

}
