<?php
declare(strict_types=1);

namespace Triniti\People;

use Gdbots\Ncr\Ncr;
use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\CommandHandlerTrait;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Ncr\NodeRef;
use Gdbots\Schemas\Pbjx\StreamId;
use Triniti\People\Exception\InvalidArgumentException;
use Triniti\Schemas\People\Mixin\DeletePerson\DeletePerson;
use Triniti\Schemas\People\Mixin\DeletePerson\DeletePersonV1Mixin;
use Triniti\Schemas\People\Mixin\Person\Person;
use Triniti\Schemas\People\Mixin\PersonDeleted\PersonDeletedV1Mixin;

final class DeletePersonHandler implements CommandHandler
{
    use CommandHandlerTrait;

    /** @var Ncr */
    private $ncr;

    /**
     * @param Ncr $ncr
     */
    public function __construct(Ncr $ncr)
    {
        $this->ncr = $ncr;
    }

    /**
     * @param DeletePerson $command
     * @param Pbjx         $pbjx
     */
    protected function handle(DeletePerson $command, Pbjx $pbjx): void
    {
        /** @var NodeRef $nodeRef */
        $nodeRef = $command->get('node_ref');
        $node = $this->ncr->getNode($nodeRef, true);

        if (!$node instanceof Person) {
            throw new InvalidArgumentException("Expected a person, got {$node::schema()->getCurie()}.");
        }

        $event = PersonDeletedV1Mixin::findOne()->createMessage();
        $pbjx->copyContext($command, $event);
        $event->set('node_ref', $nodeRef);

        if ($node->has('slug')) {
            $event->set('slug', $node->get('slug'));
        }

        $streamId = StreamId::fromString(sprintf('person.history:%s', $nodeRef->getId()));
        $pbjx->getEventStore()->putEvents($streamId, [$event]);
    }

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        return [
            DeletePersonV1Mixin::findOne()->getCurie(),
        ];
    }
}
