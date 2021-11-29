<?php

  namespace Drupal\Students\Form;

  use Drupal\Core\Form\FormBase;
  use Drupal\Core\Form\FormStateInterface;
  use Drupal\Core\Mail\MailManager;
  use Drupal\Core\Mail\MailManagerInterface;

  class BirthdayMessageForm extends FormBase {

    /**
     * @inheritDoc
     */
    public function getFormId() {
      return 'birthday-message-form';
    }

    /**
     * @inheritDoc
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
      // TODO: Implement buildForm() method.

      $config = \Drupal::config('students.settings');
      $default = $config->get('hello.name');

      $form['#theme'] = 'birthdays_template';

      $form['message'] = [
        '#type' => 'textarea',
        '#default_value' => $config->get('messaging.birthday.congratulation'),
        '#title' => t('Congratulations message'),
      ];

      $form['appellation'] = [
        '#type' => 'textfield',
        '#default_value' => $config->get('messaging.birthday.appellative'),
        '#title' => t('Form of appellation'),
      ];

      $form['from_whom'] = [
        '#type' => 'textfield',
        '#default_value' => $config->get('messaging.birthday.from'),
        '#title' => t('From whom'),
      ];

      $form['send'] = [
        '#type' => 'submit',
        '#value' => t('Send'),
        '#name' => 'submit button'
      ];

      return $form;
    }

    /**
     * @inheritDoc
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
      $connection = \Drupal::service('database');
      $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

      $query = $connection->select('students_students','stud');
      $query->fields('stud',['name','email','birth_date']);
      $query->where('MONTH(birth_date)=:my_month',['my_month'=>11]);
      $result=$query->execute()->fetchAll();

      foreach ($result as $birthday_person) {
        \Drupal::service('plugin.manager.mail')
          ->mail('students','birthday',$birthday_person->email,$language,
          [
            'appellation' => $form_state->getValue('appellation'),
            'message' => $form_state->getValue('message'),
            'from' => $form_state->getValue('from_whom'),
            'name' => $birthday_person->name,
            'to' => $birthday_person->email,
          ],NULL,TRUE);
      }
    }
  }