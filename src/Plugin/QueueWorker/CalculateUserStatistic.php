<?php

namespace Drupal\slp_statistic\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\user\Entity\User;

/**
 * Process batch API data fetcher.
 *
 * @QueueWorker(
 *   id = "calculate_user_statistic",
 *   title = @Translation("Calculate user statistic"),
 *   cron = {"time" = 2400}
 * )
 */
class CalculateUserStatistic extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    if (!\Drupal::service('slp_statistic.manager')->statisticExists((int) $data)) {
      \Drupal::service('slp_statistic.manager')->createStatistic((int) $data);
    }
  }

}