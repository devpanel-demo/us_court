<?php

namespace Drupal\usc_court_finder\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Location entity.
 *
 * @ContentEntityType(
 *   id = "usc_location",
 *   label = @Translation("Location"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\usc_court_finder\LocationListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\usc_court_finder\CourtFinderAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "usc_location",
 *   admin_permission = "administer usc_court_finder",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "office_name",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/federal-court-finder/location/{usc_location}",
 *     "edit-form" = "/federal-court-finder/location/{usc_location}/edit",
 *     "delete-form" = "/federal-court-finder/location/{usc_location}/delete",
 *     "add-form" = "/federal-court-finder/location/add",
 *     "collection" = "/admin/content/federal-court-finder/location",
 *   },
 *   field_ui_base_route = "entity.usc_location.settings"
 * )
 */
final class Location extends ContentEntityBase implements CourtFinderItemInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function label() {
    $label = '';
    if ($this->getEntityType()->getKey('label')) {
      $label = $this->getEntityKey('label');
    }
    // Add city.
    if (!$this->get('building_city')->isEmpty()) {
      $label .= ' - ' . $this->get('building_city')->value;
    }
    // Add state.
    if (!$this->get('building_state')->isEmpty()) {
      $label .= ', ' . $this->get('building_state')->value;
    }
    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['org_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Org Code'))
      ->setDescription(t('Org Code'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['unit'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Unit'))
      ->setDescription(t('Unit'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['court_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Court Type'))
      ->setDescription(t('Court Type'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['court_type_weight'] = BaseFieldDefinition::create('court_type_weight')
      ->setLabel(t('Court Type Weight'))
      ->setDescription(t('Court Type Weight'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\usc_court_finder\CourtTypeWeightItemList');

    $fields['location_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Location Id'))
      ->setDescription(t('Location Id'))
      ->setSetting('unsigned', TRUE)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['circuit_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Circuit ID'))
      ->setDescription(t('The circuit ID, which the location belongs to.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['usc_court_finder_hierarchy' => 'usc_court_finder_hierarchy']])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['district_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('District ID'))
      ->setDescription(t('The District ID, which the location belongs to.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['usc_court_finder_hierarchy' => 'usc_court_finder_hierarchy']])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['main_office'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Main Office'))
      ->setDescription(t('Main Office'))
      ->setSetting('unsigned', TRUE)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['office_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Office Name'))
      ->setDescription(t('Office Name'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Url'))
      ->setDescription(t('Url'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['phone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Phone'))
      ->setDescription(t('Phone'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['fax'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Fax'))
      ->setDescription(t('Fax'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['opens'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Opens'))
      ->setDescription(t('Opens'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['closes'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Closes'))
      ->setDescription(t('Closes'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 12,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['lunch_start'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Lunch Start'))
      ->setDescription(t('Lunch Start'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 13,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['lunch_end'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Lunch End'))
      ->setDescription(t('Lunch End'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 14,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['has_public_library'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Has Public Library'))
      ->setDescription(t('Has Public Library'))
      ->setSetting('unsigned', TRUE)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['room'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Room'))
      ->setDescription(t('Room'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 18,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['building_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Building Name'))
      ->setDescription(t('Building Name'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 19,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['building_address'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Building Address'))
      ->setDescription(t('Building Address'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['building_city'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Building City'))
      ->setDescription(t('Building City'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 21,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['building_state'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Building State'))
      ->setDescription(t('Building State'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 22,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['building_zip'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Building Zip'))
      ->setDescription(t('Building Zip'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 23,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['location_group'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Location Group'))
      ->setDescription(t('Location Group'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 24,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['address'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Address'))
      ->setDescription(t('Address'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 25,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['ejuror_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('EJuror Url'))
      ->setDescription(t('EJuror Url'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 26,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['jury_service_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Jury Service Url'))
      ->setDescription(t('Jury Service Url'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 27,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['ecf_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ECF url'))
      ->setDescription(t('ECF url'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 28,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['ecf_url2'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ECF Url 2'))
      ->setDescription(t('ECF Url 2'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 29,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['mail_box'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Mail Box'))
      ->setDescription(t('Mail Box'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 30,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['mail_zip'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Mail Zip'))
      ->setDescription(t('Mail Zip'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 31,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
