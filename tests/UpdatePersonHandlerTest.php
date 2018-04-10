<?php
declare(strict_types=1);

namespace Triniti\Tests\People;

use Acme\Schemas\Ovp\Node\VideoV1;
use Acme\Schemas\People\Command\UpdatePersonV1;
use Acme\Schemas\People\Event\PersonUpdatedV1;
use Acme\Schemas\People\Node\PersonV1;
use Gdbots\Schemas\Ncr\NodeRef;
use Gdbots\Schemas\Pbjx\Mixin\Event\Event;
use Gdbots\Schemas\Pbjx\StreamId;
use Triniti\People\UpdatePersonHandler;

final class UpdatePersonHandlerTest extends AbstractPbjxTest
{
    public function testHandle(): void
    {
        $oldNode = PersonV1::fromArray([
            '_id'  => '7afcc2f1-9654-46d1-8fc1-b0511df257db',
            'slug' => 'first-static-person',
        ]);
        $this->ncr->putNode($oldNode);

        $newNode = PersonV1::fromArray([
            '_id'  => '7afcc2f1-9654-46d1-8fc1-b0511df257db',
            'slug' => 'first-updated-static-person',
        ]);

        $command = UpdatePersonV1::create()
            ->set('node_ref', NodeRef::fromNode($oldNode))
            ->set('old_node', $oldNode)
            ->set('new_node', $newNode);

        $handler = new UpdatePersonHandler($this->ncr);
        $handler->handleCommand($command, $this->pbjx);

        $expectedEvent = PersonUpdatedV1::create();
        $expectedId = $oldNode->get('_id');
        $expectedSlug = $oldNode->get('slug');

        $this->eventStore->pipeAllEvents(
            function (Event $event, StreamId $streamId) use ($expectedEvent, $expectedId, $expectedSlug) {
                $this->assertSame($event::schema(), $expectedEvent::schema());
                $this->assertTrue($event->has('old_node'));
                $this->assertTrue($event->has('new_node'));

                $newNodeFromEvent = $event->get('new_node');

                $this->assertEquals($expectedSlug, $newNodeFromEvent->get('slug'));
                $this->assertSame(StreamId::fromString("person.history:{$expectedId}")->toString(), $streamId->toString());
                $this->assertSame($event->generateMessageRef()->toString(), (string)$newNodeFromEvent->get('last_event_ref'));
            });
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testHandleValidation(): void
    {
        $video = VideoV1::create();
        $person = PersonV1::create();

        $command = UpdatePersonV1::create();
        $command->set('node_ref', NodeRef::fromNode($person));
        $command->set('old_node', $person);
        $command->set('new_node', $video);

        $handler = new UpdatePersonHandler($this->ncr);
        $handler->handleCommand($command, $this->pbjx);
    }
}
