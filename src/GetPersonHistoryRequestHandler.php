<?php
declare(strict_types=1);

namespace Triniti\People;

use Gdbots\Ncr\AbstractGetNodeHistoryRequestHandler;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Pbjx\Mixin\GetEventsRequest\GetEventsRequest;
use Gdbots\Schemas\Pbjx\Mixin\GetEventsResponse\GetEventsResponse;
use Triniti\Schemas\People\Mixin\GetPersonHistoryRequest\GetPersonHistoryRequestV1Mixin;
use Triniti\Schemas\People\Mixin\GetPersonHistoryResponse\GetPersonHistoryResponseV1Mixin;

class GetPersonHistoryRequestHandler extends AbstractGetNodeHistoryRequestHandler
{
    /**
     * {@inheritdoc}
     */
    protected function createGetEventsResponse(GetEventsRequest $request, Pbjx $pbjx): GetEventsResponse
    {
        /** @var GetEventsResponse $response */
        $response = GetPersonHistoryResponseV1Mixin::findOne()->createMessage();
        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        return [
            GetPersonHistoryRequestV1Mixin::findOne()->getCurie(),
        ];
    }
}
