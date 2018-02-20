<?php
declare(strict_types=1);

namespace Triniti\Tests\People;

use Acme\Schemas\People\Node\PersonV1;
use Acme\Schemas\People\Request\GetPersonRequestV1;
use Acme\Schemas\People\Request\GetPersonResponseV1;
use Gdbots\Schemas\Ncr\NodeRef;
use Triniti\People\GetPersonRequestHandler;

final class GetPersonRequestHandlerTest extends AbstractPbjxTest
{
    public function testGetByNodeRefThatExists(): void
    {
        $node = PersonV1::fromArray(['_id' => '7afcc2f1-9654-46d1-8fc1-b0511df257db']);
        $nodeRef = NodeRef::fromNode($node);
        $this->ncr->putNode($node);

        $request = GetPersonRequestV1::create()->set('node_ref', $nodeRef);
        $handler = new GetPersonRequestHandler($this->ncr);
        /** @var GetPersonResponseV1 $response */
        $response = $handler->handleRequest($request, $this->pbjx);
        /** @var PersonV1 $actualNode */
        $actualNode = $response->get('node');

        $this->assertTrue($actualNode->equals($node));
    }

    /**
     * @expectedException \Gdbots\Ncr\Exception\NodeNotFound
     */
    public function testGetByNodeRefThatDoesNotExists(): void
    {
        $nodeRef = NodeRef::fromString('triniti:people:idontexist');
        $request = GetPersonRequestV1::create()->set('node_ref', $nodeRef);
        $handler = new GetPersonRequestHandler($this->ncr);
        $handler->handleRequest($request, $this->pbjx);
    }

    /**
     * @expectedException \Gdbots\Ncr\Exception\NodeNotFound
     */
    public function testGetByNothing(): void
    {
        $request = GetPersonRequestV1::create();
        $handler = new GetPersonRequestHandler($this->ncr);
        $handler->handleRequest($request, $this->pbjx);
    }

    public function testGetBySlugThatExists(): void
    {
        $node = PersonV1::fromArray([
            '_id'  => '7afcc2f1-9654-46d1-8fc1-b0511df257db',
            'slug' => 'first-static-person',
        ]);
        $nodeRef = NodeRef::fromNode($node);
        $slug = $node->get('slug');
        $this->ncr->putNode($node);

        $request = GetPersonRequestV1::create()
            ->set('qname', $nodeRef->getQName()->toString())
            ->set('slug', $slug);
        $handler = new GetPersonRequestHandler($this->ncr);
        /** @var GetPersonResponseV1 $response */
        $response = $handler->handleRequest($request, $this->pbjx);
        /** @var PersonV1 $actualNode */
        $actualNode = $response->get('node');

        $this->assertTrue($actualNode->equals($node));
    }
}
