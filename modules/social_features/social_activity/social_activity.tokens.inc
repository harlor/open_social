<?php

/**
 * @file
 * Builds placeholder replacement tokens for Social Activity module.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\node\NodeInterface;

/**
 * Implements hook_token_info().
 */
function social_activity_token_info() {
  $tokens['preview'] = [
    'name' => t('Preview.'),
    'description' => t('Preview of the related entity.'),
  ];

  $tokens['cta_button'] = [
    'name' => t('CTA button'),
    'description' => t('A call to action button.'),
  ];

  return [
    'tokens' => [
      'message' => $tokens,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function social_activity_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type === 'message' && !empty($data['message'])) {
    /** @var \Drupal\message\MessageInterface $message */
    $message = $data['message'];
    if (isset($message->field_message_related_object)) {

      $target_type = $message->field_message_related_object->target_type;
      $target_id = $message->field_message_related_object->target_id;

      $entity = \Drupal::entityTypeManager()->getStorage($target_type)
        ->load($target_id);
      if ($entity) {
        $date_formatter = \Drupal::service('date.formatter');
        $date_format = 'social_long_date';
        $summary = '';

        if ($target_type === 'post') {
          /** @var \Drupal\social_post\Entity\PostInterface $entity */
          $author_name = $entity->getOwner()->getDisplayName();
          $date = $date_formatter->format($entity->getCreatedTime(), $date_format);

          // Get the summary of the comment.
          if ($entity->hasField('field_post') && !$entity->field_post->isEmpty()) {
            $summary = _social_comment_get_summary($entity->field_post->value);
          }

          // Prepare the link to post.
          $link = Url::fromRoute('entity.post.canonical',
            ['post' => $entity->id()],
            ['absolute' => TRUE]
          )->toString();

          // Prepare the preview information.
          $preview_info = [
            '#theme' => 'message_entity_preview',
            '#author_name' => $author_name,
            '#date' => $date,
            '#summary' => $summary,
            '#link' => $link,
          ];

          // Prepare the CTA button markup.
          $cta_button = [
            '#theme' => 'message_cta_button',
            '#link' => $link,
            '#text' => t('See the post'),
          ];
        }
        elseif ($target_type === 'group_content' && $entity instanceof GroupContentInterface) {
          $group_content_type = $entity->getGroupContentType();

          if ($group_content_type !== NULL) {
            $entity_bundle_label = mb_strtolower($group_content_type->label());
          }

          /** @var \Drupal\node\NodeInterface $node */
          $node = $entity->getEntity();

          if ($node instanceof NodeInterface) {
            $link = Url::fromRoute('entity.node.canonical',
              ['node' => $node->id()],
              ['absolute' => TRUE]
            )->toString();

            $author_name = $node->getOwner()->getDisplayName();
            $date = $date_formatter->format($node->getCreatedTime(), $date_format);
            $summary = $node->getTitle();

            // Prepare the preview.
            $preview_info = [
              '#theme' => 'message_entity_preview',
              '#author_name' => $author_name,
              '#date' => $date,
              '#summary' => $summary,
              '#link' => $link,
            ];

            // Prepare the CTA button markup.
            $cta_button = [
              '#theme' => 'message_cta_button',
              '#link' => $link,
              '#text' => t('Read more about this @entity_bundle_label', [
                '@entity_bundle_label' => $entity_bundle_label,
              ]),
            ];

          }
        }

        // Replace tokens.
        foreach ($tokens as $name => $original) {
          switch ($name) {
            case 'preview':
              if (!empty($preview_info)) {
                $replacements[$original] = \Drupal::service('renderer')->renderPlain($preview_info);
              }
              break;

            case 'cta_button':
              if (!empty($cta_button)) {
                $replacements[$original] = \Drupal::service('renderer')->renderPlain($cta_button);
              }
              break;
          }
        }
      }
    }
  }

  return $replacements;
}
