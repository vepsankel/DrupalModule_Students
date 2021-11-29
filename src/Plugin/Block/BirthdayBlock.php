<?php

  namespace Drupal\students\Plugin\Block;

  use Drupal\Core\Block\BlockBase;

  /**
   * @Block(
   *   id = "birthday_block",
   *   admin_label = @Translation("Birthday block"),
   * )
   */
  class BirthdayBlock extends BlockBase {

    /**
     * @inheritDoc
     */
    public function build() {
      return \Drupal::formBuilder()->getForm('Drupal\students\Form\BirthdayMessageForm');
    }
  }