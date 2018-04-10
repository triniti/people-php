<?php
declare(strict_types=1);

namespace Triniti\People;

use Gdbots\Ncr\AbstractSearchNodesRequestHandler;
use Triniti\Schemas\People\Mixin\SearchPeopleRequest\SearchPeopleRequestV1Mixin;

class SearchPeopleRequestHandler extends AbstractSearchNodesRequestHandler
{
    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        return [
            SearchPeopleRequestV1Mixin::findOne()->getCurie(),
        ];
    }
}
