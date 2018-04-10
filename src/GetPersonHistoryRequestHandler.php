<?php
declare(strict_types=1);

namespace Triniti\People;

use Gdbots\Ncr\AbstractGetNodeHistoryRequestHandler;
use Gdbots\Pbj\SchemaCurie;
use Triniti\Schemas\People\Mixin\Person\PersonV1Mixin;

class GetPersonHistoryRequestHandler extends AbstractGetNodeHistoryRequestHandler
{
    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        $curie = PersonV1Mixin::findOne()->getCurie();
        return [
            SchemaCurie::fromString("{$curie->getVendor()}:{$curie->getPackage()}:request:get-person-history-request"),
        ];
    }
}
