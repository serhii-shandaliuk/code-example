<?php

namespace Drupal\slp_statistic;

use Drupal\config_entity_cloner\Service\ConfigEntityCloner;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\slp_school\SchoolManagerInterface;
use Drupal\user\Entity\User;

/**
 * Class InteractiveManager
 *
 * @package Drupal\slp_statistic
 */
class StatisticManager implements StatisticManagerInterface {
  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * The logger chanel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The user storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $userStorage;

  /**
   * The taxonomy vocabulary storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $slpStatistic;

  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $nodeStorage;


  /**
   * EntityConfig cloner.
   *
   * @var \Drupal\config_entity_cloner\Service\ConfigEntityCloner
   */
  protected $cloner;

  /**
   * The Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $currentUser;

  /**
   * The current user.
   *
   * @var EntityInterface
   */
  protected EntityInterface $currentUserEntity;

  /**
   * The school manager.
   *
   * @var \Drupal\slp_school\SchoolManagerInterface
   */
  protected SchoolManagerInterface $schoolManager;

  /**
   * InteractiveManager Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\slp_school\SchoolManagerInterface $school_manager
   *   The school manager.
 */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, LoggerChannelFactoryInterface $logger_factory, AccountInterface $current_user, SchoolManagerInterface $school_manager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->userStorage = $entityTypeManager->getStorage('user');
    $this->nodeStorage = $entityTypeManager->getStorage('node');
    $this->slpStatistic = $entityTypeManager->getStorage('slp_statistic');
    $this->logger = $logger_factory->get('slp_statistic');
    $this->currentUser = $current_user;
    $this->currentUserEntity = $this->userStorage->load($current_user->id());
    $this->schoolManager = $school_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function createStatistic(int $uid = 0): void {
    if (!$uid) {
      $uid = $this->currentUser->id();
    }

    try {
      $user = User::load($uid);
      $role = $user->get('field_school_role')->value;
      if (!$user->get('field_expired')->value) {
        $values = [];
        if (!$role || $role === 'student') {
          $lessons = $user->get('field_lessons')->getValue();
          $output = _slp_school_build_lessons_chart_data($lessons, $uid);
          $lessons_count = $average_lessons = 0;
          if (!empty($output['data'])) {
            $lessons_count++;
            $average_lessons = array_sum($output['data']) / count($output['data']);
          }

          $courses = $user->get('field_courses')->getValue();
          if ($courses) {
            $courses = array_column($courses, 'target_id');
            $courses = array_unique($courses);
            foreach ($courses as $course) {
              $properties = ['field_course' => $course];
              $lessons = $this->nodeStorage->loadByProperties($properties);
              if (empty($lessons)) {
                continue;
              }

              $output = _slp_school_build_lessons_chart_data($lessons, $uid);
              if (empty($output['data'])) {
                continue;
              }

              $average_lessons += array_sum($output['data']) / count($output['data']);
              $lessons_count++;
            }

            if ($average_lessons && $lessons_count) {
              $rating = $average_lessons / $lessons_count;
              $values = [
                'uid' => $uid,
                'field_rating' => $rating,
                'field_type' => 'student',
              ];
            }
          }
        }
        elseif ($role === 'teacher' || $role === 'author'|| $role === 'director') {
          $all_students = $this->schoolManager->getStudents($uid);
          $existing_students = $this->schoolManager->getActiveStudents($uid);
          if ($all_students && $existing_students) {
            $retention_rate = (count($existing_students) / count($all_students)) * 100;
            $i = $days_from_created = $active_days = 0;
            foreach ($existing_students as $student) {
              $user_entity = $this->userStorage->load($student);
              if (!$user_entity) {
                continue;
              }

              $created = $user_entity->get('created')->value;
              $days_from_created += round((time() - $created) / 8640);
              $changed = $user_entity->get('changed')->value;
              $active_days += round(($changed - $created) / 8640);
              $i++;
            }

            $all_days = round($days_from_created / $i);
            $average_students_days = round($active_days / $i);
            $average_days_percents = ((count($existing_students) * $average_students_days) / (count($existing_students) * $all_days)) * 100;
            $rating = ($retention_rate + $average_days_percents) / 2;

            $values = [
              'uid' => $uid,
              'field_rating' => round($rating),
              'field_retention_rate' => round($retention_rate),
              'field_average_students_days' => round($average_students_days),
              'field_type' => 'teacher',
            ];
          }
        }

        if ($values) {
          $this->slpStatistic->create($values)->save();
        }

      }
    } catch (\Exception $exception) {
      $this->logger->error($exception->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createUserReferencesStatistic(int $uid = 0): void {
    if (!$uid) {
      $uid = $this->currentUser->id();
    }

    $users = $this->schoolManager->getActiveStudents($uid);
    $exists = FALSE;
    if ($this->statisticExists(end($users))) {
      $exists = TRUE;
    }

    $users = array_merge($users, $this->schoolManager->getActiveTeachers($uid));
    if ($this->statisticExists(end($users)) && $exists) {
      return;
    }

    $users = array_unique($users);
    foreach ($users as $user) {
      if ($this->statisticExists($user)) {
        continue;
      }

      $this->createStatistic($user);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function statisticExists(int $uid = 0): bool {
    if (!$uid) {
      $uid = $this->currentUser->id();
    }

    $user_query = $this->slpStatistic->getQuery();
    $user_query->accessCheck();
    $user_query->condition('uid', $uid);
    $user_query->condition('created', strtotime('today'), '>=');
    return (bool) $user_query->execute();
  }

}
