<?php

namespace Drupal\piksel;

/**
 * A value object to store the piksel media data.
 */
class Piksel {

  /**
   * The piksel media ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The piksel media URL.
   *
   * @var string
   */
  protected $url;

  /**
   * The piksel media thumbnail URL.
   *
   * @var string
   */
  protected $thumbnailUrl;


  /**
   * The piksel media description.
   *
   * @var string
   */
  protected $description;

  /**
   * The piksel media alt.
   *
   * @var string
   */
  protected $alt;

  /**
   * Constructs a new Piksel object.
   *
   * @param string $id
   *   The piksel media ID.
   * @param string $url
   *   The piksel media URL.
   * @param string $thumbnail_url
   *   The piksel media thumbnail URL.
   * @param string $description
   *   The piksel media description.
   * @param string $alt
   *   The piksel media alt.
   */
  public function __construct(string $id, string $url, string $thumbnail_url, string $description = '', string $alt = '') {
    $this->id = $id;
    $this->url = $url;
    $this->thumbnailUrl = $thumbnail_url;
    $this->description = $description;
    $this->alt = $alt;
  }

  /**
   * Gets the piksel media ID.
   *
   * @return string
   *   The piksel media ID.
   */
  public function getId(): string {
    return $this->id;
  }

  /**
   * Gets the piksel media URL.
   *
   * @return string
   *   The piksel media URL.
   */
  public function getUrl(): string {
    return $this->url;
  }

  /**
   * Gets the piksel media thumbnail URL.
   *
   * @return string
   *   The piksel media thumbnail URL.
   */
  public function getThumbnailUrl(): string {
    return $this->thumbnailUrl;
  }

  /**
   * Gets the piksel media description.
   *
   * @return string
   *   The piksel media description.
   */
  public function getDescription(): string {
    return $this->description;
  }

  /**
   * Gets the piksel media ALT text.
   *
   * @return string
   *   The piksel media ALT text.
   */
  public function getAlt(): string {
    return $this->alt;
  }

}
