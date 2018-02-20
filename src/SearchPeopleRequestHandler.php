<?php
declare(strict_types=1);

namespace Triniti\People;

use Gdbots\Ncr\NcrSearch;
use Gdbots\Pbjx\RequestHandler;
use Gdbots\Pbjx\RequestHandlerTrait;
use Gdbots\QueryParser\Enum\BoolOperator;
use Gdbots\QueryParser\Node\Field;
use Gdbots\QueryParser\Node\Word;
use Gdbots\QueryParser\ParsedQuery;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Triniti\Schemas\People\Mixin\Person\PersonV1Mixin;
use Triniti\Schemas\People\Mixin\SearchPeopleRequest\SearchPeopleRequest;
use Triniti\Schemas\People\Mixin\SearchPeopleRequest\SearchPeopleRequestV1Mixin;
use Triniti\Schemas\People\Mixin\SearchPeopleResponse\SearchPeopleResponse;
use Triniti\Schemas\People\Mixin\SearchPeopleResponse\SearchPeopleResponseV1Mixin;

final class SearchPeopleRequestHandler implements RequestHandler
{
    use RequestHandlerTrait;

    /** @var NcrSearch */
    private $ncrSearch;

    /**
     * @param NcrSearch $ncrSearch
     */
    public function __construct(NcrSearch $ncrSearch)
    {
        $this->ncrSearch = $ncrSearch;
    }

    /**
     * @param SearchPeopleRequest $request
     *
     * @return SearchPeopleResponse
     */
    protected function handle(SearchPeopleRequest $request): SearchPeopleResponse
    {
        /** @var SearchPeopleResponse $response */
        $response = SearchPeopleResponseV1Mixin::findOne()->createMessage();

        $parsedQuery = ParsedQuery::fromArray(json_decode(
            $request->get('parsed_query_json', '{}'),
            true
        ));

        $prohibited = BoolOperator::PROHIBITED();

        // if status is not specified in some way, default to not
        // showing any deleted nodes.
        if (!$request->has('status')
            && !$request->has('statuses')
            && !$request->isInSet('fields_used', 'status')
        ) {
            $parsedQuery->addNode(
                new Field('status', new Word(NodeStatus::DELETED, $prohibited), $prohibited)
            );
        }

        $this->ncrSearch->searchNodes(
            $request,
            $parsedQuery,
            $response,
            [PersonV1Mixin::findOne()->getQName()]
        );

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
