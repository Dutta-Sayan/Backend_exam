<?php

namespace Drupal\Tests\file_download_link\Kernel;

use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Class for testing file_download_link formatter with tokens.
 *
 * @group file_download_link
 * @requires module token
 */
class FileDownloadLinkTokenTest extends FileDownloadLinkTestBase {

  use UserCreationTrait;

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
  protected function setUp(): void {
    parent::setUp();
    $this->setUpCurrentUser(['uid' => 99]);
  }

  /**
   * {@inheritdoc}
   *
   * A note on cache tags and tokens...as the first test case 'no tokens,
   * default settings' demonstrates, if there are no tokens anywhere, then
   * the cache tags just come from the file being linked. However, if there
   * are ANY tokens (even invalid ones) the Token::generate() method will
   * add the cacheability of all data objects passed to the token service,
   * even if there are no tokens for that object. That's why in the test case
   * 'tokens for text file', there's a cache tag for node:1. Even though there
   * are no node tokens, the node is passed as part of the data array to the
   * token service. With regard to the current user tokens, the user is not
   * passed as an object in the data array. As a result its cache tags only
   * end up in the bubbleable_metadata when that type of token is truly
   * present.
   */
  public static function providerTestFileDownloadLink() {
    return [
      'no tokens, default settings' => [
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
          ],
        ],
      ],
      'tokens for text file' => [
        'field' => 'field_file',
        'settings' => [
          'link_text' => 'The extension is [file:extension]',
          'new_tab' => FALSE,
          'force_download' => FALSE,
        ],
        'output' => [
          [
            'title' => 'The extension is txt',
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
              'tags' => ['file:2', 'node:1'],
              'contexts' => [],
              'max-age' => -1,
            ],
          ]
        ],
      ],
      'tokens for image field' => [
        'field' => 'field_image',
        'settings' => [
          'link_text' => 'The image width is [node:field_image:width]',
          'new_tab' => FALSE,
          'force_download' => FALSE,
        ],
        'output' => [
          [
            'title' => 'The image width is 40',
            'options' => [
              'attributes' => [
                'class' => [
                  'file-download',
                  'file-download-image',
                  'file-download-png',
                ],
              ],
            ],
            'cache' => [
              'tags' => ['file:1', 'node:1'],
              'contexts' => [],
              'max-age' => -1,
            ],
          ],
        ],
      ],
      'tokens for image file' => [
        'field' => 'field_image',
        'settings' => [
          'link_text' => 'The extension is [file:extension]',
          'new_tab' => FALSE,
          'force_download' => FALSE,
        ],
        'output' => [
          [
            'title' => 'The extension is png',
            'options' => [
              'attributes' => [
                'class' => [
                  'file-download',
                  'file-download-image',
                  'file-download-png',
                ],
              ],
            ],
            'cache' => [
              'tags' => ['file:1', 'node:1'],
              'contexts' => [],
              'max-age' => -1,
            ],
          ]
        ],
      ],
      'tokens in link title' => [
        'field' => 'field_image',
        'settings' => [
          'link_text' => 'Testing tokens in title',
          'link_title' => 'Download [file:extension]',
          'new_tab' => FALSE,
          'force_download' => FALSE,
        ],
        'output' => [
          [
            'title' => 'Testing tokens in title',
            'options' => [
              'attributes' => [
                'class' => [
                  'file-download',
                  'file-download-image',
                  'file-download-png',
                ],
                'title' => 'Download png',
              ],
            ],
            'cache' => [
              'tags' => ['file:1', 'node:1'],
              'contexts' => [],
              'max-age' => -1,
            ],
          ],
        ],
      ],
      'tokens in custom classes' => [
        'field' => 'field_image',
        'settings' => [
          'link_text' => 'Testing tokens in classes',
          'new_tab' => FALSE,
          'force_download' => FALSE,
          'custom_classes' => 'link-[file:mime] static-class',
        ],
        'output' => [
          [
            'title' => 'Testing tokens in classes',
            'options' => [
              'attributes' => [
                'class' => [
                  'file-download',
                  'file-download-image',
                  'file-download-png',
                  'link-image-png',
                  'static-class',
                ],
              ],
            ],
            'cache' => [
              'tags' => ['file:1', 'node:1'],
              'contexts' => [],
              'max-age' => -1,
            ],
          ],
        ],
      ],
      'tokens for multi-valued field (i.e. testing delta handling)' => [
        'field' => 'field_image',
        'settings' => [
          'link_text' => '[node:field_image:alt]',
          'new_tab' => FALSE,
          'force_download' => FALSE,
        ],
        'output' => [
          [
            'title' => 'This alt text is for the first image.',
            'options' => [
              'attributes' => [
                'class' => [
                  'file-download',
                  'file-download-image',
                  'file-download-png',
                ],
              ],
            ],
            'cache' => [
              'tags' => ['file:1', 'node:1'],
              'contexts' => [],
              'max-age' => -1,
            ],
          ],
          [
            'title' => 'When delta is 1 we should see this alt text. Let\'s add special chars & test them!',
            'options' => [
              'attributes' => [
                'class' => [
                  'file-download',
                  'file-download-image',
                  'file-download-png',
                ],
              ],
            ],
            'cache' => [
              'tags' => ['file:1', 'node:1'],
              'contexts' => [],
              'max-age' => -1,
            ],
          ],
        ],
      ],
      'invalid tokens are cleared' => [
        'field' => 'field_file',
        'settings' => [
          'link_text' => '[fake:token]',
          'link_title' => '[fake:token]',
          'custom_classes' => '[fake:token]',
          'new_tab' => FALSE,
          'force_download' => FALSE,
        ],
        'output' => [
          [
            'title' => 'file.txt',
            'options' => [
              'attributes' => [
                'class' => [
                  'file-download',
                  'file-download-plain',
                  'file-download-text',
                ],
              ],
            ],
            'cache' => [
              'tags' => ['file:2', 'node:1'],
              'contexts' => [],
              'max-age' => -1,
            ],
          ],
        ],
      ],
      'current user tokens' => [
        'field' => 'field_image',
        'settings' => [
          'link_text' => 'Download this, [current-user:uid]',
          'new_tab' => FALSE,
          'force_download' => FALSE,
        ],
        'output' => [
          [
            'title' => 'Download this, 99',
            'options' => [
              'attributes' => [
                'class' => [
                  'file-download',
                  'file-download-image',
                  'file-download-png',
                ],
              ],
            ],
            'cache' => [
              'tags' => ['file:1', 'user:99', 'node:1'],
              'contexts' => ['user'],
              'max-age' => -1,
            ],
          ],
        ],
      ],
      'current user tokens in link title' => [
        'field' => 'field_image',
        'settings' => [
          'link_title' => 'You know you want it, [current-user:uid]',
          'new_tab' => FALSE,
          'force_download' => FALSE,
        ],
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
                'title' => 'You know you want it, 99',
              ],
            ],
            'cache' => [
              'tags' => ['file:1', 'user:99', 'node:1'],
              'contexts' => ['user'],
              'max-age' => -1,
            ],
          ],
        ],
      ],
    ];
  }

}
