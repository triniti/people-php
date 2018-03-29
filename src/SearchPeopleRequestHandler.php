<?php
declare(strict_types=1);

namespace Triniti\People;

use Gdbots\Ncr\AbstractSearchNodesRequestHandler;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Ncr\Mixin\SearchNodesRequest\SearchNodesRequest;
use Gdbots\Schemas\Ncr\Mixin\SearchNodesResponse\SearchNodesResponse;
use Triniti\Schemas\People\Mixin\SearchPeopleRequest\SearchPeopleRequestV1Mixin;
use Triniti\Schemas\People\Mixin\SearchPeopleResponse\SearchPeopleResponseV1Mixin;

class SearchPeopleRequestHandler extends AbstractSearchNodesRequestHandler
{
    /**
     * {@inheritdoc}
     */
    protected function createSearchNodesResponse(SearchNodesRequest $request, Pbjx $pbjx): SearchNodesResponse
    {
        /** @var SearchNodesResponse $response */
        $response = SearchPeopleResponseV1Mixin::findOne()->createMessage();
        return $response;
    }

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
