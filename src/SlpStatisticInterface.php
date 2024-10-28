<?php

declare(strict_types=1);

namespace Drupal\slp_statistic;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a slp statistic entity type.
 */
interface SlpStatisticInterface extends ContentEntityInterface, EntityOwnerInterface {

}
