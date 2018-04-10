<?php
declare(strict_types=1);

namespace Triniti\People;

use Gdbots\Ncr\AbstractUpdateNodeHandler;
use Gdbots\Pbj\SchemaCurie;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Ncr\Mixin\NodeUpdated\NodeUpdated;
use Gdbots\Schemas\Ncr\Mixin\UpdateNode\UpdateNode;
use Triniti\Schemas\People\Mixin\Person\Person;
use Triniti\Schemas\People\Mixin\Person\PersonV1Mixin;

class UpdatePersonHandler extends AbstractUpdateNodeHandler
{
    use PbjxHelperTrait;

    /**
     * {@inheritdoc}
     */
    protected function beforePutEvents(NodeUpdated $event, UpdateNode $command, Pbjx $pbjx): void
    {
        parent::beforePutEvents($event, $command, $pbjx);

        /** @var Person $newNode */
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
        $curie = PersonV1Mixin::findOne()->getCurie();
        return [
            SchemaCurie::fromString("{$curie->getVendor()}:{$curie->getPackage()}:command:update-person"),
        ];
    }
}
