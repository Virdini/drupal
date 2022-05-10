<?php

namespace Drupal\vcustom\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Base class for node blocks.
 */
abstract class NodeTeaserBlockBase extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * List of taxonomy term vocabularies.
   *
   * @var array
   */
  protected $vids = [];

  /**
   * List of node bundles.
   *
   * @var array
   */
  protected $bundles = [];

  /**
   * Node view mode.
   *
   * @var string
   */
  protected $view_mode = 'teaser';

  /**
   * Node term fieled.
   *
   * @var string
   */
  protected $term_field = 'field_term';

  /**
   * List of container attributes.
   *
   * @var array
   */
  protected $container_attributes = [];

  /**
   * Show paget or not.
   *
   * @var bool
   */
  protected $pager = FALSE;

  /**
   * Number of items to display.
   *
   * @var int
   */
  protected $qty = 9;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * The node view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $nodeViewBuilder;

  /**
   * The taxonomy term storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $termStorage;

  /**
   * Constructs a new object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, LanguageManagerInterface $language_manager, EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->routeMatch = $route_match;
    $this->languageManager = $language_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
    $this->nodeStorage = $this->entityTypeManager->getStorage('node');
    $this->nodeViewBuilder = $this->entityTypeManager->getViewBuilder('node');
    $this->termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('language_manager'),
      $container->get('entity_type.manager'),
      $container->get('entity.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get entities
    if (!($entities = $this->getEntities())) {
      return [];
    }

    // Add count attribute
    if (isset($this->container_attributes['data-num'])) {
      $this->container_attributes['data-num'] = count($entities);
    }

    $build = [
      'nodes' => $this->nodeViewBuilder->viewMultiple($entities, $this->view_mode) + [
        '#type' => 'container',
        '#attributes' => $this->container_attributes,
      ],
    ];

    // Add pager
    if ($this->pager) {
      $build['pager'] = [
        '#type' => 'pager',
        '#quantity' => 3,
      ];
    }

    return $build;
  }

  /**
   * Get entity objects from storage.
   *
   * @param array $tids
   *   The taxonomy term ids.
   * @param array $exclude
   *   The entity ids to exclude.
   *
   * @return array
   *   The list of entity objects.
   */
  protected function getEntities(array $tids = [], array $exclude = []) {
    return $this->nodeStorage->loadMultiple($this->getIds($tids, $exclude));
  }

  /**
   * Get entity ids from storage.
   *
   * @param array $tids
   *   The taxonomy term ids.
   * @param array $exclude
   *   The entity ids to exclude.
   *
   * @return array
   *   The list of node ids.
   */
  protected function getIds(array $tids, array $exclude) {
    $query = $this->getQuery($tids, $exclude);

    // Add sort criteria
    $this->addQuerySort($query);

    // Add limits
    if ($this->pager && $this->qty) {
      $query->pager($this->qty);
    }
    elseif ($this->qty) {
      $query->range(0, $this->qty);
    }

    return $query->execute();
  }

  /**
   * Get query instance.
   *
   * @param array $tids
   *   The taxonomy term ids.
   * @param array $exclude
   *   The entity ids to exclude.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The query instance.
   */
  protected function getQuery(array $tids, array $exclude) {
    $query = $this->nodeStorage->getQuery();

    // Filter nodes by taxonomy terms
    if (!empty($tids)) {
      $query->condition($this->term_field, $tids, 'IN');
    }

    // Exclude nodes
    if (!empty($exclude)) {
      $query->condition('nid', $exclude, 'NOT IN');
    }

    $query->condition('status', TRUE)
          ->condition('type', $this->bundles, 'IN')
          ->condition('langcode', [
              $this->languageManager->getCurrentLanguage()->getId(),
              LanguageInterface::LANGCODE_NOT_SPECIFIED,
              LanguageInterface::LANGCODE_NOT_APPLICABLE,
            ], 'IN');

    // Add specific query conditions
    $this->addQueryConditions($query);

    return $query;
  }

  /**
   * Add specific query conditions.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The query instance.
   */
  protected function addQueryConditions(QueryInterface &$query) {

  }

  /**
   * Add sort criteria.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The query instance.
   */
  protected function addQuerySort(QueryInterface &$query) {
    $query->sort('sticky', 'DESC')
          ->sort('created', 'DESC');
  }

  /**
   * Gets the route entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The current route entity.
   */
  protected function getEntity() {
    switch ($this->routeMatch->getRouteName()) {
      case 'entity.node.canonical':
        return $this->routeMatch->getParameter('node');
        break;

      case 'entity.taxonomy_term.canonical':
        return $this->routeMatch->getParameter('taxonomy_term');
        break;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = ['languages:language_content'];

    // Add pager context
    if ($this->pager) {
      $contexts[] = 'url.query_args.pagers';
    }

    return Cache::mergeContexts(parent::getCacheContexts(), $contexts);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = Cache::buildTags('node_list', $this->bundles);

    return Cache::mergeTags(parent::getCacheTags(), $tags);
  }

}
