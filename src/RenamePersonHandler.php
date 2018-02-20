<?php
declare(strict_types=1);

namespace Triniti\People;

use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\CommandHandlerTrait;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Ncr\NodeRef;
use Gdbots\Schemas\Pbjx\StreamId;
use Triniti\People\Exception\InvalidArgumentException;
use Triniti\Schemas\People\Mixin\Person\PersonV1Mixin;
use Triniti\Schemas\People\Mixin\PersonRenamed\PersonRenamedV1Mixin;
use Triniti\Schemas\People\Mixin\RenamePerson\RenamePerson;
use Triniti\Schemas\People\Mixin\RenamePerson\RenamePersonV1Mixin;

final class RenamePersonHandler implements CommandHandler
{
    use CommandHandlerTrait;

    /**
     * @param RenamePerson $command
     * @param Pbjx         $pbjx
     */
    protected function handle(RenamePerson $command, Pbjx $pbjx): void
    {
        if ($command->get('new_slug') === $command->get('old_slug')) {
            return;
        }

        /** @var NodeRef $nodeRef */
        $nodeRef = $command->get('node_ref');

        if ($nodeRef->getQName() !== PersonV1Mixin::findOne()->getQName()) {
            throw new InvalidArgumentException("Expected a person, got {$nodeRef}.");
        }

        $event = PersonRenamedV1Mixin::findOne()->createMessage();
        $pbjx->copyContext($command, $event);

        $event->set('new_slug', $command->get('new_slug'))
            ->set('old_slug', $command->get('old_slug'))
            ->set('node_status', $command->get('node_status'))
            ->set('node_ref', $nodeRef);

        $streamId = StreamId::fromString(sprintf('person.history:%s', $nodeRef->getId()));
        $pbjx->getEventStore()->putEvents($streamId, [$event]);
    }

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        return [
            RenamePersonV1Mixin::findOne()->getCurie(),
        ];
    }
}
