<?php
declare(strict_types=1);

namespace Triniti\People;

use Gdbots\Pbjx\EventStore\StreamSlice;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Pbjx\RequestHandler;
use Gdbots\Pbjx\RequestHandlerTrait;
use Gdbots\Schemas\Pbjx\StreamId;
use Triniti\Schemas\People\Mixin\GetPersonHistoryRequest\GetPersonHistoryRequest;
use Triniti\Schemas\People\Mixin\GetPersonHistoryRequest\GetPersonHistoryRequestV1Mixin;
use Triniti\Schemas\People\Mixin\GetPersonHistoryResponse\GetPersonHistoryResponse;
use Triniti\Schemas\People\Mixin\GetPersonHistoryResponse\GetPersonHistoryResponseV1Mixin;

final class GetPersonHistoryRequestHandler implements RequestHandler
{
    use RequestHandlerTrait;

    /**
     * @param GetPersonHistoryRequest $request
     * @param Pbjx                    $pbjx
     *
     * @return GetPersonHistoryResponse
     */
    protected function handle(GetPersonHistoryRequest $request, Pbjx $pbjx): GetPersonHistoryResponse
    {
        /** @var StreamId $streamId */
        $streamId = $request->get('stream_id');

        // if someone is getting "creative" and trying to pull a different stream
        // then we'll just return an empty slice.  no soup for you.
        if ('person.history' === $streamId->getTopic()) {
            $slice = $pbjx->getEventStore()->getStreamSlice(
                $streamId,
                $request->get('since'),
                $request->get('count'),
                $request->get('forward')
            );
        } else {
            $slice = new StreamSlice([], $streamId, $request->get('forward'));
        }

        $schema = GetPersonHistoryResponseV1Mixin::findOne();
        /** @var GetPersonHistoryResponse $response */
        $response = $schema->createMessage();

        return $response
            ->set('has_more', $slice->hasMore())
            ->set('last_occurred_at', $slice->getLastOccurredAt())
            ->addToList('events', $slice->toArray()['events']);
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
