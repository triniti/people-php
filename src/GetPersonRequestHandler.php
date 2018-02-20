<?php
declare(strict_types=1);

namespace Triniti\People;

use Gdbots\Ncr\Exception\NodeNotFound;
use Gdbots\Ncr\IndexQueryBuilder;
use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\SchemaQName;
use Gdbots\Pbjx\RequestHandler;
use Gdbots\Pbjx\RequestHandlerTrait;
use Triniti\Schemas\People\Mixin\GetPersonRequest\GetPersonRequest;
use Triniti\Schemas\People\Mixin\GetPersonRequest\GetPersonRequestV1Mixin;
use Triniti\Schemas\People\Mixin\GetPersonResponse\GetPersonResponse;
use Triniti\Schemas\People\Mixin\GetPersonResponse\GetPersonResponseV1Mixin;

final class GetPersonRequestHandler implements RequestHandler
{
    use RequestHandlerTrait;

    /** @var Ncr */
    private $ncr;

    /**
     * @param Ncr $ncr
     */
    public function __construct(Ncr $ncr)
    {
        $this->ncr = $ncr;
    }

    /**
     * @param GetPersonRequest $request
     *
     * @return GetPersonResponse
     *
     * @throws NodeNotFound
     */
    protected function handle(GetPersonRequest $request): GetPersonResponse
    {
        if ($request->has('node_ref')) {
            $node = $this->ncr->getNode($request->get('node_ref'), $request->get('consistent_read'));
        } elseif ($request->has('slug')) {
            $qname = SchemaQName::fromString($request->get('qname'));
            $query = IndexQueryBuilder::create($qname, 'slug', $request->get('slug'))
                ->setCount(1)
                ->build();
            $result = $this->ncr->findNodeRefs($query);
            if (!$result->count()) {
                throw new NodeNotFound('Unable to locate person.');
            }

            $node = $this->ncr->getNode($result->getNodeRefs()[0], $request->get('consistent_read'));
        } else {
            throw new NodeNotFound('No method to locate person.');
        }

        $schema = GetPersonResponseV1Mixin::findOne();
        /** @var GetPersonResponse $response */
        $response = $schema->createMessage();
        return $response->set('node', $node);
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
