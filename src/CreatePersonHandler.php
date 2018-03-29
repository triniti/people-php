<?php
declare(strict_types=1);

namespace Triniti\People;

use Gdbots\Ncr\AbstractCreateNodeHandler;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Ncr\Mixin\CreateNode\CreateNode;
use Gdbots\Schemas\Ncr\Mixin\Node\Node;
use Gdbots\Schemas\Ncr\Mixin\NodeCreated\NodeCreated;
use Triniti\Schemas\People\Mixin\CreatePerson\CreatePersonV1Mixin;
use Triniti\Schemas\People\Mixin\Person\Person;
use Triniti\Schemas\People\Mixin\PersonCreated\PersonCreatedV1Mixin;

class CreatePersonHandler extends AbstractCreateNodeHandler
{
    /**
     * {@inheritdoc}
     */
    protected function isNodeSupported(Node $node): bool
    {
        return $node instanceof Person;
    }

    /**
     * {@inheritdoc}
     */
    protected function createNodeCreated(CreateNode $command, Pbjx $pbjx): NodeCreated
    {
        /** @var NodeCreated $event */
        $event = PersonCreatedV1Mixin::findOne()->createMessage();
        return $event;
    }

    /**
     * {@inheritdoc}
     */
    protected function beforePutEvents(NodeCreated $event, CreateNode $command, Pbjx $pbjx): void
    {
        parent::beforePutEvents($event, $command, $pbjx);
        $node = $event->get('node');
        $node->set('status', NodeStatus::PUBLISHED());
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
