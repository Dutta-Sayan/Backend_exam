<?php

namespace Drupal\delete_user\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Form to delete graduated students.
 */
class DeleteStudentForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'delete_student_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['delete_user'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete Graduated Students'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Role of user to delete.
    $role = 'student';
    // Fetching all the student users..
    $uids = \Drupal::entityQuery('user')->accessCheck(FALSE)->condition('roles', $role)->execute();
    // The uids to be deleted will be stored.
    $uids_delete = [];
    foreach ($uids as $user_id) {
      // Fetching the student user.
      $user = User::load($user_id);
      // Fetching the passing year.
      $year = $user->get('field_passing_year')->getValue();
      $passing_year = $year[0]['value'];

      // Fetching the current time.
      $current_time = \Drupal::time()->getCurrentTime();
      // Converting the current time to date format.
      $current_year = date('Y', $current_time);

      // If graduated more than one year ago, student is to be deleted.
      if (($current_year - $passing_year) > 1) {
        array_push($uids_delete, $user_id);
      }
    }
    // Batch operations to be performed.
    $operations = [
      ['delete_students', [$uids_delete]],
    ];
    $batch = [
      'title' => $this->t('Deleting Graduated Students ...'),
      'operations' => $operations,
      'finished' => 'delete_students_finished',
    ];
    // Setting the batch operation.
    batch_set($batch);
  }

}
