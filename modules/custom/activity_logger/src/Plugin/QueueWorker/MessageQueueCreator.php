<?php

namespace Drupal\activity_logger\Plugin\QueueWorker;

use Drupal\activity_creator\Plugin\ActivityActionManager;
use Drupal\Core\Queue\QueueFactory;
use Drupal\node\Entity\Node;

/**
 * A report worker.
 *
 * @QueueWorker(
 *   id = "activity_logger_message",
 *   title = @Translation("Process activity_logger_message queue."),
 *   cron = {"time" = 60}
 * )
 *
 * This QueueWorker is responsible for creating message items from the queue
 */
class MessageQueueCreator extends MessageQueueBase {

  /**
   * @var \Drupal\activity_creator\Plugin\ActivityActionManager
   */
  protected $actionManager;

  /**
   *
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, QueueFactory $queue, ActivityActionManager $actionManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $queue);

    $this->actionManager = $actionManager;
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {

    // First make sure it's an actual entity.
    if ($entity = Node::load($data['entity_id'])) {
      // Check if it's created more than 20 seconds ago.
      $timestamp = $entity->getCreatedTime();
      // Current time.
      $now = time();
      $diff = abs($now - $timestamp);

      // Items must be at least 5 seconds old.
      if ($diff <= 5 && $now > $timestamp) {
        // Wait for 100 milliseconds.
        // We don't want to flood the DB with unprocessable queue items.
        usleep(100000);
        $this->createQueueItem('activity_logger_message', $data);
      }
      else {
        // Trigger the create action for enttites.
        $create_action = $this->actionManager->createInstance('create_entitiy_action');
        $create_action->createMessage($entity);
      }
    }
  }

}
