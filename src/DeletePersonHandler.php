<?php
declare(strict_types=1);

namespace Triniti\People;

use Gdbots\Ncr\AbstractDeleteNodeHandler;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Ncr\Mixin\DeleteNode\DeleteNode;
use Gdbots\Schemas\Ncr\Mixin\Node\Node;
use Gdbots\Schemas\Ncr\Mixin\NodeDeleted\NodeDeleted;
use Triniti\Schemas\People\Mixin\DeletePerson\DeletePersonV1Mixin;
use Triniti\Schemas\People\Mixin\Person\Person;
use Triniti\Schemas\People\Mixin\PersonDeleted\PersonDeletedV1Mixin;

class DeletePersonHandler extends AbstractDeleteNodeHandler
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
    protected function createNodeDeleted(DeleteNode $command, Pbjx $pbjx): NodeDeleted
    {
        /** @var NodeDeleted $event */
        $event = PersonDeletedV1Mixin::findOne()->createMessage();
        return $event;
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
