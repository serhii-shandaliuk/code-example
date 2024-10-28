<?php

declare(strict_types=1);

namespace Drupal\slp_statistic\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\slp_statistic\SlpStatisticInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the slp statistic entity class.
 *
 * @ContentEntityType(
 *   id = "slp_statistic",
 *   label = @Translation("Slp statistic"),
 *   label_collection = @Translation("Slp statistics"),
 *   label_singular = @Translation("slp statistic"),
 *   label_plural = @Translation("slp statistics"),
 *   label_count = @PluralTranslation(
 *     singular = "@count slp statistics",
 *     plural = "@count slp statistics",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\slp_statistic\SlpStatisticListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\slp_statistic\Form\SlpStatisticForm",
 *       "edit" = "Drupal\slp_statistic\Form\SlpStatisticForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\slp_statistic\Routing\SlpStatisticHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "slp_statistic",
 *   admin_permission = "administer slp_statistic",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/slp-statistic",
 *     "add-form" = "/slp-statistic/add",
 *     "canonical" = "/slp-statistic/{slp_statistic}",
 *     "edit-form" = "/slp-statistic/{slp_statistic}",
 *     "delete-form" = "/slp-statistic/{slp_statistic}/delete",
 *     "delete-multiple-form" = "/admin/content/slp-statistic/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.slp_statistic.settings",
 * )
 */
final class SlpStatistic extends ContentEntityBase implements SlpStatisticInterface {

  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(self::class . '::getDefaultEntityOwner')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the slp statistic was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
