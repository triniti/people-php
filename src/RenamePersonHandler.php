<?php
declare(strict_types=1);

namespace Triniti\People;

use Gdbots\Ncr\AbstractRenameNodeHandler;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Ncr\Mixin\Node\Node;
use Gdbots\Schemas\Ncr\Mixin\NodeRenamed\NodeRenamed;
use Gdbots\Schemas\Ncr\Mixin\RenameNode\RenameNode;
use Triniti\Schemas\People\Mixin\Person\Person;
use Triniti\Schemas\People\Mixin\PersonRenamed\PersonRenamedV1Mixin;
use Triniti\Schemas\People\Mixin\RenamePerson\RenamePersonV1Mixin;

class RenamePersonHandler extends AbstractRenameNodeHandler
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
    protected function createNodeRenamed(RenameNode $command, Pbjx $pbjx): NodeRenamed
    {
        /** @var NodeRenamed $event */
        $event = PersonRenamedV1Mixin::findOne()->createMessage();
        return $event;
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
