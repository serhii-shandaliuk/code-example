<?php

namespace Drupal\slp_statistic;

use Drupal\user\Entity\User;

/**
 * Interface SchoolManagerInterface.
 *
 * @package Drupal\slp_statistic
 */
interface StatisticManagerInterface {

  /**
   * Create statistic entity for user.
   *
   * @param int $uid
   *  User uid.
   */
  public function createStatistic(int $uid = 0): void;

  /**
   * Create statistic entity for all user references.
   *
   * @param int $uid
   *  User uid.
   */
  public function createUserReferencesStatistic(int $uid = 0): void;

  /**
   * Returns if statistic exists.
   *
   * @param int $uid
   *  User uid.
   *
   * @return bool
   */
  public function statisticExists(int $uid = 0): bool;

}
