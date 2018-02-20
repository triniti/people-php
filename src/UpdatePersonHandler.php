<?php
declare(strict_types=1);

namespace Triniti\People;

use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\CommandHandlerTrait;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Pbjx\StreamId;
use Triniti\Schemas\People\Mixin\Person\Person;
use Triniti\Schemas\People\Mixin\PersonUpdated\PersonUpdatedV1Mixin;
use Triniti\Schemas\People\Mixin\UpdatePerson\UpdatePerson;
use Triniti\Schemas\People\Mixin\UpdatePerson\UpdatePersonV1Mixin;

final class UpdatePersonHandler implements CommandHandler
{
    use CommandHandlerTrait;

    /**
     * @param UpdatePerson $command
     * @param Pbjx         $pbjx
     */
    protected function handle(UpdatePerson $command, Pbjx $pbjx): void
    {
        $event = PersonUpdatedV1Mixin::findOne()->createMessage();
        $pbjx->copyContext($command, $event);

        /** @var Person $newNode */
        $newNode = clone $command->get('new_node');
        $newNode
            ->set('updated_at', $event->get('occurred_at'))
            ->set('updater_ref', $event->get('ctx_user_ref'))
            ->set('last_event_ref', $event->generateMessageRef());

        if ($command->has('old_node')) {
            $oldNode = $command->get('old_node');
            $event->set('old_node', $oldNode);

            $newNode
                // status SHOULD NOT change during an update, use the appropriate
                // command to change a status (delete, publish, etc.)
                ->set('status', $oldNode->get('status'))
                // created_at and creator_ref MUST NOT change
                ->set('created_at', $oldNode->get('created_at'))
                ->set('creator_ref', $oldNode->get('creator_ref'))
                // slug SHOULD NOT change during an update, use "rename-person"
                ->set('slug', $oldNode->get('slug'));
        }

        // people are only published or deleted, enforce it.
        if (!NodeStatus::DELETED()->equals($newNode->get('status'))) {
            $newNode->set('status', NodeStatus::PUBLISHED());
        }

        $event
            ->set('node_ref', $command->get('node_ref'))
            ->set('new_node', $newNode);

        $streamId = StreamId::fromString(sprintf('person.history:%s', $newNode->get('_id')));
        $pbjx->getEventStore()->putEvents($streamId, [$event]);
    }

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        return [
            UpdatePersonV1Mixin::findOne()->getCurie(),
        ];
    }
}
