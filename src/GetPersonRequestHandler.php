<?php
declare(strict_types=1);

namespace Triniti\People;

use Gdbots\Ncr\AbstractGetNodeRequestHandler;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Ncr\Mixin\GetNodeRequest\GetNodeRequest;
use Gdbots\Schemas\Ncr\Mixin\GetNodeResponse\GetNodeResponse;
use Triniti\Schemas\People\Mixin\GetPersonRequest\GetPersonRequestV1Mixin;
use Triniti\Schemas\People\Mixin\GetPersonResponse\GetPersonResponseV1Mixin;

class GetPersonRequestHandler extends AbstractGetNodeRequestHandler
{
    /**
     * {@inheritdoc}
     */
    protected function createGetNodeResponse(GetNodeRequest $request, Pbjx $pbjx): GetNodeResponse
    {
        /** @var GetNodeResponse $response */
        $response = GetPersonResponseV1Mixin::findOne()->createMessage();
        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public static function handlesCuries(): array
    {
        return [
            GetPersonRequestV1Mixin::findOne()->getCurie(),
        ];
    }
}
