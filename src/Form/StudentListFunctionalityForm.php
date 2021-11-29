<?php

  namespace Drupal\Students\Form;

  use Drupal\Core\Form\FormBase;
  use Drupal\Core\Form\FormStateInterface;

  class StudentListFunctionalityForm extends FormBase {

    /**
     * @inheritDoc
     */
    public function getFormId() {
      return 'function_list_functionality_form';
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
      $triggering_element_name = $form_state->getTriggeringElement()['#name'];
      $config = \Drupal::config('students.settings');

      //remove group pressed
      if (str_starts_with($triggering_element_name, 'remove_group')) {
        $pieces = explode('_', $triggering_element_name);
        $last_word = array_pop($pieces);

        $connection = \Drupal::database();
        $students_of_group =
          $connection->select('students_students', 'stud')
            ->fields('stud', ['student_group'])
            ->condition('student_group', $last_word)
            ->countQuery()
            ->execute()
            ->fetchField();

        if ($students_of_group > 0) {
          $form_state->setError($form_state->getTriggeringElement(),
            $config->get('form.list_functionality.group.error_message_delete_unempty'));
          return;
        }
      }

      //add group pressed
      if ($triggering_element_name == 'add_group') {
        $new_group = $form_state->getValue('add_group_textfield');

        if (!preg_match($config->get('form.list_functionality.group.regex'), $new_group)) {
          $form_state->setErrorByName('add_group_textfield',
            $config->get('form.list_functionality.group.error_message_invalid'));
          return;
        }

        if ($this->groupAlreadyExists($new_group)) {
          $form_state->setError($form_state->getTriggeringElement(),
            $config->get('form.list_functionality.group.error_message_already_exists'));
          return;
        }
        return;
      }

      //set group pressed
      if ($triggering_element_name == 'set_groups') {
        foreach ($form_state->getValues() as $field => $value) {
          if ($this->groupAlreadyExists($field)) {
            if (str_starts_with($value, '#')) {
              $value = substr($value, -6);
              $form_state->setValue($field,$value);
            }
            if (!preg_match($config->get('form.list_functionality.color.regex'),$value)) {
              $form_state->setErrorByName('field',$config->get('form.list_functionality.color.error_message'));
            }
          }
        }
      }
    }

    /**
     * @inheritDoc
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
      $form['#theme'] = 'students_list_functionality_template';
      $config = \Drupal::config('students.settings');

      $form['add_group_textfield'] = [
        '#type' => 'textfield',
        '#maxlength' => $config->get('form.list_functionality.group.max_len'),
        '#title' => t('New Group'),
      ];

      $form['add_group_button'] = [
        '#type' => 'submit',
        '#value' => 'Add',
        '#name' => 'add_group'
      ];

      $form['set'] = [
        '#type' => 'submit',
        '#value' => 'Set',
        '#name' => 'set_groups',
      ];

      return $form;
    }

    /**
     * @inheritDoc
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

      $triggering_element_value = $form_state->getTriggeringElement()['#value'];

      if (str_starts_with($triggering_element_value, 'Remove')) {
        $pieces = explode(' ',$triggering_element_value );
        $group_to_remove = array_pop($pieces);

        $connection = \Drupal::database();
        try{
          $result = $connection->delete('students_groups')
            ->condition('student_group', $group_to_remove)
            ->execute();
        } catch (\Exception $e) {
          watchdog_exception('students_database', $e, "Could not delete student group");
        }
      }

      //set group pressed
      if (str_starts_with($triggering_element_value, 'Set')) {

        //Hopefully, we don't have too many groups
        foreach ($form_state->getValues() as $field => $value) {
          if ($this->groupAlreadyExists($field)) {
            try {
              $this->addGroupToDB($field, $value);
            } catch (\Exception $e) {
              watchdog_exception('students_database', $e, "Could not add student group");
            }
          }
        }
      }

      if (str_starts_with($triggering_element_value, 'Add')) {
        $new_group = $form_state->getValue('add_group_textfield');
        $this->addGroupToDB($new_group);
      }
    }

    private function groupAlreadyExists($name) : bool {
      $connection = \Drupal::database();

      try{
        $groups_number =
          $connection->select('students_groups', 'grup')
            ->fields('grup', ['student_group'])
            ->condition('student_group', $name)
            ->countQuery()
            ->execute()
            ->fetchField();

        if ($groups_number > 0) {
          return TRUE;
        }
      } catch (\Exception $e) {
        watchdog_exception('students_database', $e, "Could not verify that group already exists");
        return TRUE;
      }

      return FALSE;
    }

    /**
     * @throws \Exception
     */
    private function addGroupToDB($name, $color = NULL) {
      $connection = \Drupal::database();
      $default_color = \Drupal::config('students.settings')->get('form.list_functionality.color.default_color');
      $color = $color?:$default_color;

      return $connection->merge('students_groups')
        ->key('student_group', $name)
        ->fields(['student_group' => $name, '$color' => $color])
        ->execute();
    }
  }