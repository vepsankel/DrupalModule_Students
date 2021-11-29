<?php

  namespace Drupal\Students\Form;

  use Drupal\Core\Form\FormBase;
  use Drupal\Core\Form\FormStateInterface;

  class NewStudentForm extends FormBase {

    /**
     * @inheritDoc
     */
    public function getFormId() {
      return "student_new_student_form";
    }

    /**
     * @inheritDoc
     */
    public function buildForm(array $form, FormStateInterface $form_state, $id = NULL, $is_editable = NULL) {
      $config = \Drupal::config('students.settings');

      $form['id'] = [
        '#type' => 'hidden',
        '#value' => $id,
      ];

      $form['is_editable'] = [
        '#type' => 'hidden',
        '#value' => $is_editable,
      ];

      $form['name'] = [
        '#type' => 'textfield',
        '#title' => t('Name'),
        '#size' => 50,
        '#maxlength' => $config->get('form.student.name.max_len'),
        '#disabled' => TRUE,
        //The variant below is possible,
        //but the appearing error message is unsuggestive
        //'#pattern' => $config->get('form.student.name.regex')
      ];

      $form['last_name'] = [
        '#type' => 'textfield',
        '#title' => t('Last name'),
        '#size' => 50,
        '#maxlength' => $config->get('form.student.name.max_len'),
        '#disabled' => TRUE,
      ];

      $form['email'] = [
        '#type' => 'email',
        '#title' => t('Email'),
        '#disabled' => TRUE,
      ];

      $form['birth_date'] = [
        '#type' => 'date',
        '#title' => t('Date of Birth'),
        '#disabled' => TRUE,
      ];

      $form['group'] = [
        '#options' => [
          0 => 'none',
        ],
        '#type' => 'select',
        '#title' => t('Group'),
        '#size' => 1,
        '#disabled' => TRUE,
      ];

      $form['is_student'] = [
        '#type' => 'checkbox',
        '#title' => t('Is currently a student'),
        '#disabled' => TRUE,
      ];

      $form['discard'] = [
        '#type' => 'submit',
        '#value' => t('Back to list'),
        '#submit' => ['redirect_to_student_list'],
        '#name' => 'discard_button',
      ];

      return $form;
    }

    /**
     * @inheritDoc
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
      $connection = \Drupal::service('database');
      $form_state->setRedirect('students.list');

      $triggering_name = $form_state->getTriggeringElement()['#name'];

      //Discard button pressed - do nothing
      if ($triggering_name == 'discard_button') {
        return;
      }

      //Edit button pressed - try to enable editing
      if ($triggering_name == 'edit_button') {
        $form_state->setRedirect('student.edit', ['id' => $form['id']['#value'],'is_editable' => '1']);
        return;
      }

      $student_id = $form['id']['#value'];

      //Student deletion handling
      if ($triggering_name == 'delete_button') {
        try{
          $result = $connection
            ->delete('students_students')
            ->condition('student_id',$student_id)
            ->execute();
          return;
        } catch (\Exception $e) {
          watchdog_exception('students_database', $e, "Could not delete student");
        }
      }

      //Else - save button pressed
      //Insert into db student information
      $group = $form['group']['#options'][$form_state->getValue('group')];

      $key_key = $student_id ? 'student_id' : 'email';
      $key_value = $student_id ?: $form_state->getValue('email');

      try{
        $result = $connection
          ->merge('students_students')
          ->key($key_key, $key_value)
          ->fields([
            'name' => $form_state->getValue('name'),
            'last_name' => $form_state->getValue('last_name'),
            'email' => $form_state->getValue('email'),
            'birth_date' => $form_state->getValue('birth_date'),
            'is_student' => $form_state->getValue('is_student'),
            'student_group' => $group == 'none'? NULL : $group,
          ])
          ->execute();
      } catch (\Exception $e) {
        watchdog_exception('students_database', $e, "Could not update student information");
      }

    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
      $triggering_name = $form_state->getTriggeringElement()['#name'];

      //no validation needed if editing is not enabled
      //(edit_button only enables editing)
      if ($triggering_name == 'discard_button'
        || $triggering_name == 'edit_button'
        || $triggering_name == 'delete_button') {
        return;
      }

      //trim name and last name
      $name = trim($form_state->getValue('name'));
      $form_state->setValue('name', $name);

      $last_name = trim($form_state->getValue('last_name'));
      $form_state->setValue('last_name', $last_name);

      //get regular expressions
      $config = \Drupal::config('students.settings');
      $name_regex = $config->get('form.student.name.regex');
      $last_name_regex = $config->get('form.student.last_name.regex');

      //validate fields
      if (!preg_match($name_regex, $name)) {
        $form_state->setErrorByName('name', t($config->get('form.student.name.error_message')));
      }

      if (!preg_match($last_name_regex, $last_name)) {
        $form_state->setErrorByName('last_name', t($config->get('form.student.name.error_message')));
      }

      if (!\Drupal::service('email.validator')
        ->isValid($form_state->getValue('email'))) {
        $form_state->setErrorByName('email', t($config->get('form.student.email.error_message_invalid_email')));
      }

      $birth_date = \DateTime::createFromFormat('Y-m-d', $form_state->getValue('birth_date'));

      if (!$birth_date || !($form_state->getValue('birth_date') === $birth_date->format('Y-m-d'))) {
        $form_state->setErrorByName('birth_date', t($config->get('form.student.birth_date.error_message_invalid_date')));
      } else {
        $today = new \DateTime();
        $diff = $today->diff($birth_date);
        $min_diff = $config->get('form.student.birth_date.min_age');
        $max_diff = $config->get('form.student.birth_date.max_age');

        if ($diff->y < $min_diff || $diff->y > $max_diff) {
          $form_state->setErrorByName('birth_date',
            t($config->get('form.student.birth_date.error_message_incorrect_date')));
        }
      }

      //Check if email is used
      //Users with different IDs should not have identical emails
      $connection = \Drupal::service('database');
      try{
        $result = $connection->select('students_students', 'stud')
          ->fields('stud', ['email','student_id'])
          ->condition('stud.student_id',$form['id']['#value'],'<>')
          ->condition('stud.email', $form_state->getValue('email'), 'LIKE')
          ->range(0, 1)
          ->execute();

        foreach ($result as $record) {
          $form_state->setErrorByName('email',
            t($config->get('form.student.email.error_message_email_taken')));
        }
      } catch (\Exception $e) {
        watchdog_exception('students_database', $e, "Could not verify unique email");
        $form_state->setErrorByName('email',
          t($config->get('form.student.email.error_message_email_taken')));
      }
    }
  }
