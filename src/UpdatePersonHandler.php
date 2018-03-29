<?php
declare(strict_types=1);

namespace Triniti\People;

use Gdbots\Ncr\AbstractUpdateNodeHandler;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Ncr\Mixin\Node\Node;
use Gdbots\Schemas\Ncr\Mixin\NodeUpdated\NodeUpdated;
use Gdbots\Schemas\Ncr\Mixin\UpdateNode\UpdateNode;
use Triniti\Schemas\People\Mixin\Person\Person;
use Triniti\Schemas\People\Mixin\PersonUpdated\PersonUpdatedV1Mixin;
use Triniti\Schemas\People\Mixin\UpdatePerson\UpdatePersonV1Mixin;

class UpdatePersonHandler extends AbstractUpdateNodeHandler
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
    protected function createNodeUpdated(UpdateNode $command, Pbjx $pbjx): NodeUpdated
    {
        /** @var NodeUpdated $event */
        $event = PersonUpdatedV1Mixin::findOne()->createMessage();
        return $event;
    }

    /**
     * {@inheritdoc}
     */
    protected function beforePutEvents(NodeUpdated $event, UpdateNode $command, Pbjx $pbjx): void
    {
        parent::beforePutEvents($event, $command, $pbjx);

        $newNode = $event->get('new_node');

        // people are only published or deleted, enforce it.
        if (!NodeStatus::DELETED()->equals($newNode->get('status'))) {
            $newNode->set('status', NodeStatus::PUBLISHED());
        }
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
