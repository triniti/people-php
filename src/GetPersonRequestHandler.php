<?php
declare(strict_types=1);

namespace Triniti\People;

use Gdbots\Ncr\AbstractGetNodeRequestHandler;
use Gdbots\Pbj\SchemaCurie;
use Triniti\Schemas\People\Mixin\Person\PersonV1Mixin;

class GetPersonRequestHandler extends AbstractGetNodeRequestHandler
{
    use PbjxHelperTrait;

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        $curie = PersonV1Mixin::findOne()->getCurie();
        return [
            SchemaCurie::fromString("{$curie->getVendor()}:{$curie->getPackage()}:request:get-person-request"),
        ];
    }
}
