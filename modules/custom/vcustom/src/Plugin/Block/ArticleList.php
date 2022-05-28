<?php

namespace Drupal\vcustom\Plugin\Block;

/**
 * Provides a 'ArticleList'
 *
 * @Block(
 *   id = "vcustom_articles",
 *   admin_label = "Article list"
 * )
 */
class ArticleList extends NodeTeaserBlockBase {

  /**
   * {@inheritdoc}
   */
  protected $bundles = ['article'];

  /**
   * {@inheritdoc}
   */
  protected $view_mode = 'teaser';

  /**
   * {@inheritdoc}
   */
  protected $pager = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $qty = 12;

  /**
   * {@inheritdoc}
   */
  protected $container_attributes = ['class' => ['articles-grid']];

}
