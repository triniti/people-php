<?php
declare(strict_types=1);

namespace Triniti\People;

use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\CommandHandlerTrait;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Pbjx\StreamId;
use Triniti\Schemas\People\Mixin\CreatePerson\CreatePerson;
use Triniti\Schemas\People\Mixin\CreatePerson\CreatePersonV1Mixin;
use Triniti\Schemas\People\Mixin\Person\Person;
use Triniti\Schemas\People\Mixin\PersonCreated\PersonCreatedV1Mixin;

final class CreatePersonHandler implements CommandHandler
{
    use CommandHandlerTrait;

    /**
     * @param CreatePerson $command
     * @param Pbjx         $pbjx
     */
    protected function handle(CreatePerson $command, Pbjx $pbjx): void
    {
        $event = PersonCreatedV1Mixin::findOne()->createMessage();
        $pbjx->copyContext($command, $event);

        /** @var Person $node */
        $node = clone $command->get('node');
        $node
            ->clear('updated_at')
            ->clear('updater_ref')
            ->set('status', NodeStatus::PUBLISHED())
            ->set('created_at', $event->get('occurred_at'))
            ->set('creator_ref', $event->get('ctx_user_ref'))
            ->set('last_event_ref', $event->generateMessageRef());

        $event->set('node', $node);
        $streamId = StreamId::fromString(sprintf('person.history:%s', $node->get('_id')));
        $pbjx->getEventStore()->putEvents($streamId, [$event]);
    }

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        return [
            CreatePersonV1Mixin::findOne()->getCurie(),
        ];
    }
}
