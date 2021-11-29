<?php

  namespace Drupal\students\Plugin\Block;

  use Drupal\Core\Block\BlockBase;

  /**
   * @Block(
   *   id = "students_list_functionality",
   *   admin_label = @Translation("List Functionality"),
   * )
   */

  class StudentListFunctionalityBlock extends BlockBase {

    /**
     * @inheritDoc
     */
    public function build() {
      return \Drupal::formBuilder()->getForm('\Drupal\students\Form\StudentListFunctionalityForm');
    }
  }