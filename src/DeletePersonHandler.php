<?php
declare(strict_types=1);

namespace Triniti\People;

use Gdbots\Ncr\AbstractDeleteNodeHandler;
use Gdbots\Pbj\SchemaCurie;
use Triniti\Schemas\People\Mixin\Person\PersonV1Mixin;

class DeletePersonHandler extends AbstractDeleteNodeHandler
{
    use PbjxHelperTrait;

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        $curie = PersonV1Mixin::findOne()->getCurie();
        return [
            SchemaCurie::fromString("{$curie->getVendor()}:{$curie->getPackage()}:command:delete-person"),
        ];
    }
}
