<?php
declare(strict_types=1);

namespace Triniti\Tests\People;

use Acme\Schemas\People\Event\PersonCreatedV1;
use Acme\Schemas\People\Event\PersonDeletedV1;
use Acme\Schemas\People\Event\PersonUpdatedV1;
use Acme\Schemas\People\Request\GetPersonHistoryRequestV1;
use Gdbots\Pbj\WellKnown\Microtime;
use Gdbots\Schemas\Pbjx\StreamId;
use Triniti\People\GetPersonHistoryRequestHandler;

final class GetPersonHistoryRequestHandlerTest extends AbstractPbjxTest
{
    public function testHandleRequest(): void
    {
        $this->prepareEventsForStreamId('person.history:1234');
        // test default
        $request = GetPersonHistoryRequestV1::fromArray([
            'stream_id' => 'person.history:1234',
            'since'     => Microtime::create(),
        ]);
        $handler = new GetPersonHistoryRequestHandler();
        $response = $handler->handleRequest($request, $this->pbjx);
        $events = $response->get('events');
        $this->assertInstanceOf('Acme\Schemas\People\Request\GetPersonHistoryResponseV1', $response);
        $this->assertFalse($response->get('has_more'));
        $this->assertEquals(3, count($events));
        $this->assertInstanceOf('Acme\Schemas\People\Event\PersonDeletedV1', $events[0]);
        $this->assertInstanceOf('Acme\Schemas\People\Event\PersonUpdatedV1', $events[1]);
        $this->assertInstanceOf('Acme\Schemas\People\Event\PersonCreatedV1', $events[2]);
    }

    public function testHandleRequestWithCount(): void
    {
        $this->prepareEventsForStreamId('person.history:4321');
        // test with count
        $request = GetPersonHistoryRequestV1::fromArray([
            'stream_id' => 'person.history:4321',
            'since'     => Microtime::create(),
            'count'     => 2,
        ]);
        $handler = new GetPersonHistoryRequestHandler();
        $response = $handler->handleRequest($request, $this->pbjx);
        $event = $response->get('events');
        //$this->assertTrue($response->get('has_more'));
        $this->assertEquals(2, count($response->get('events')));
        $this->assertInstanceOf('Acme\Schemas\People\Event\PersonDeletedV1', $event[0]);
        $this->assertInstanceOf('Acme\Schemas\People\Event\PersonUpdatedV1', $event[1]);
    }

    public function testHandleRequestWithForward(): void
    {
        $this->prepareEventsForStreamId('person.history:0000');
        // test with forward
        $request = GetPersonHistoryRequestV1::fromArray([
            'stream_id' => 'person.history:0000',
            'forward'   => true,
        ]);
        $handler = new GetPersonHistoryRequestHandler();
        $response = $handler->handleRequest($request, $this->pbjx);
        $event = $response->get('events');
        $this->assertFalse($response->get('has_more'));
        $this->assertEquals(3, count($response->get('events')));
        $this->assertInstanceOf('Acme\Schemas\People\Event\PersonCreatedV1', $event[0]);
        $this->assertInstanceOf('Acme\Schemas\People\Event\PersonDeletedV1', $event[count($event) - 1]);
    }

    /**
     * @param string $id
     *
     * @return void
     */
    private function prepareEventsForStreamId(string $id): void
    {
        $streamId = StreamId::fromString($id);
        $createEvent = PersonCreatedV1::create();
        $updateEvent = PersonUpdatedV1::create();
        $deleteEvent = PersonDeletedV1::create();
        $this->pbjx->getEventStore()->putEvents($streamId, [$createEvent, $updateEvent, $deleteEvent]);
    }
}
