<?php

/**
 * @file
 * Post update functions for USC Core.
 */

/**
 * Updates for USCGOV-427.
 *
 * Fixing basic_simple artifacts.
 */
function usc_core_post_update_10001(&$sandbox) {
  $keyvalue = \Drupal::service('keyvalue');
  $bundle_field_map = $keyvalue->get('entity.definitions.bundle_field_map')->get('node');
  foreach ($bundle_field_map as &$field) {
    $bundles = &$field['bundles'];
    if (array_key_exists('basic_simple', $bundles)) {
      unset($bundles['basic_simple']);
    }
  }
  $keyvalue->get('entity.definitions.bundle_field_map')->set('node', $bundle_field_map);
}

/**
 * Updates for USCGOV-405.
 *
 * Fixing warnings of the field report page.
 */
function usc_core_post_update_10002(&$sandbox) {
  $keyvalue = \Drupal::service('keyvalue');
  $bundle_field_map = $keyvalue->get('entity.definitions.bundle_field_map')->get('file');
  $broken_bundles = ['document', 'image', 'audio'];
  foreach ($bundle_field_map as &$field) {
    $bundles = &$field['bundles'];
    foreach ($broken_bundles as $broken_bundle) {
      if (array_key_exists($broken_bundle, $bundles)) {
        unset($bundles[$broken_bundle]);
      }
    }

  }
  $keyvalue->get('entity.definitions.bundle_field_map')->set('file', $bundle_field_map);
}
