<?php
declare(strict_types=1);

namespace Triniti\Tests\People;

use Acme\Schemas\People\Command\CreatePersonV1;
use Acme\Schemas\People\Event\PersonCreatedV1;
use Acme\Schemas\People\Node\PersonV1;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Pbjx\Mixin\Event\Event;
use Gdbots\Schemas\Pbjx\StreamId;
use Triniti\People\CreatePersonHandler;

final class CreatePersonHandlerTest extends AbstractPbjxTest
{
    public function testHandleCommand(): void
    {
        $node = PersonV1::create()
            ->set('title', 'test-person');

        $command = CreatePersonV1::create()
            ->set('node', $node);
        $expectedEvent = PersonCreatedV1::create();
        $expectedId = $node->get('_id');

        $handler = new CreatePersonHandler();
        $handler->handleCommand($command, $this->pbjx);

        $this->eventStore->pipeAllEvents(function (Event $event, StreamId $streamId)
        use ($expectedEvent, $expectedId) {
            $actualNode = $event->get('node');

            $this->assertSame($event::schema(), $expectedEvent::schema());
            $this->assertSame(NodeStatus::PUBLISHED(), $actualNode->get('status'));
            $this->assertSame('test-person', $actualNode->get('title'));
            $this->assertSame(StreamId::fromString("person.history:{$expectedId}")->toString(), $streamId->toString());
            $this->assertSame($event->generateMessageRef()->toString(), (string)$actualNode->get('last_event_ref'));
        });
    }
}
