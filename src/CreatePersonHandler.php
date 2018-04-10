<?php
declare(strict_types=1);

namespace Triniti\People;

use Gdbots\Ncr\AbstractCreateNodeHandler;
use Gdbots\Pbj\SchemaCurie;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Ncr\Mixin\CreateNode\CreateNode;
use Gdbots\Schemas\Ncr\Mixin\NodeCreated\NodeCreated;
use Triniti\Schemas\People\Mixin\Person\PersonV1Mixin;

class CreatePersonHandler extends AbstractCreateNodeHandler
{
    use PbjxHelperTrait;

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
        $curie = PersonV1Mixin::findOne()->getCurie();
        return [
            SchemaCurie::fromString("{$curie->getVendor()}:{$curie->getPackage()}:command:create-person"),
        ];
    }
}
