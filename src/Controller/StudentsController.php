<?php

    namespace Drupal\students\Controller;

    use Drupal\Core\Controller\ControllerBase;

    class StudentsController extends ControllerBase
    {
        public function list() {
            return [
                '#cache' => [
                  'max-age' => 0,
                ],
                '#theme' => 'students_list_template'
            ];
        }
    }