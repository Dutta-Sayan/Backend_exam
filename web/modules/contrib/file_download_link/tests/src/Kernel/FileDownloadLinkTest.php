<?php

namespace Drupal\Tests\file_download_link\Kernel;

/**
 * Class for testing file_download_link formatter.
 *
 * @group file_download_link
 */
class FileDownloadLinkTest extends FileDownloadLinkTestBase {

  public static function providerTestFileDownloadLink() {
    return [
      'file (default)' => [
        'field' => 'field_file',
        'settings' => [],
        'output' => [
          [
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
              'tags' => ['file:2'],
              'contexts' => [],
              'max-age' => -1,
            ],
          ]
        ],
      ],
      'file (custom)' => [
        'field' => 'field_file',
        'settings' => [
          'link_text' => '',
          'link_title' => 'Click for file',
          'new_tab' => FALSE,
          'force_download' => FALSE,
          'custom_classes' => 'Howdy! p@rtner',
        ],
        'output' => [
          [
            'title' => 'file.txt',
            'options' => [
              'attributes' => [
                'class' => [
                  'file-download',
                  'file-download-text',
                  'file-download-plain',
                  'Howdy',
                  'prtner',
                ],
                'title' => 'Click for file',
              ],
            ],
            'cache' => [
              'tags' => ['file:2'],
              'contexts' => [],
              'max-age' => -1,
            ],
          ],
        ],
      ],
      'image (default)' => [
        'field' => 'field_image',
        'settings' => [],
        'output' => [
          [
            'title' => 'Download',
            'options' => [
              'attributes' => [
                'class' => [
                  'file-download',
                  'file-download-image',
                  'file-download-png',
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
          ]
        ],
      ],
      'image (custom)' => [
        'field' => 'field_image',
        'settings' => [
          'link_text' => '',
          'link_title' => 'Click for image',
          'new_tab' => FALSE,
          'force_download' => FALSE,
          'custom_classes' => 'Howdy! p@rtner',
        ],
        'output' => [
          [
            'title' => 'file.png',
            'options' => [
              'attributes' => [
                'class' => [
                  'file-download',
                  'file-download-image',
                  'file-download-png',
                  'Howdy',
                  'prtner',
                ],
                'title' => 'Click for image',
              ],
            ],
            'cache' => [
              'tags' => ['file:1'],
              'contexts' => [],
              'max-age' => -1,
            ],
          ],
        ],
      ],
    ];
  }

}
