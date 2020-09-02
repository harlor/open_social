<?php

namespace Drupal\mentions\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * MentionsDelete handles event 'mentions.delete'.
 */
class MentionsDelete implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events['mentions.delete'][] = ['onMentionsDelete', 0];
    return $events;
  }

  /**
   * Event handler.
   */
  public function onMentionsDelete($event) {
    $config = \Drupal::config('mentions.mentions');
    $config_mentions_events = $config->get('mentions_events');
    $action_id = $config_mentions_events['delete'];
    if (empty($action_id)) {
      return;
    }
    $entity_storage = \Drupal::service('entity_type.manager')->getStorage('action');
    $action = $entity_storage->load($action_id);
    $action_plugin = $action->getPlugin();
    if (!empty($action_id)) {
      $action_plugin->execute(FALSE);
    }
  }

}
